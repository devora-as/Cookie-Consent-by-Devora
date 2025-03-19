<?php

/**
 * Admin Documentation Template
 *
 * Documentation page for the cookie consent plugin.
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
        <h1><?php _e('Cookie Consent Documentation', 'custom-cookie-consent'); ?></h1>
    </div>

    <div class="cookie-consent-admin-nav">
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-consent'); ?>">
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
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-analytics'); ?>">
            <?php _e('Analytics & Statistics', 'custom-cookie-consent'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-documentation'); ?>" class="active">
            <?php _e('Documentation', 'custom-cookie-consent'); ?>
        </a>
    </div>

    <div class="cookie-consent-admin-card">
        <h2><?php _e('GDPR Compliance Guide', 'custom-cookie-consent'); ?></h2>

        <div class="cookie-documentation-section">
            <h3><?php _e('Understanding Granular Consent', 'custom-cookie-consent'); ?></h3>
            <p><?php _e('The GDPR requires that users be able to give separate consent for different purposes. This plugin implements proper granular consent with four distinct categories:', 'custom-cookie-consent'); ?></p>

            <ul>
                <li><strong><?php _e('Necessary Cookies:', 'custom-cookie-consent'); ?></strong> <?php _e('Always enabled, essential for the website to function properly (e.g., session cookies, security cookies)', 'custom-cookie-consent'); ?></li>
                <li><strong><?php _e('Analytics Cookies:', 'custom-cookie-consent'); ?></strong> <?php _e('Enabled only when the user consents to analytics (e.g., Google Analytics, statistics tracking)', 'custom-cookie-consent'); ?></li>
                <li><strong><?php _e('Functional Cookies:', 'custom-cookie-consent'); ?></strong> <?php _e('Enabled only when the user consents to functional features (e.g., remembering preferences, enhanced features)', 'custom-cookie-consent'); ?></li>
                <li><strong><?php _e('Marketing Cookies:', 'custom-cookie-consent'); ?></strong> <?php _e('Enabled ONLY when the user explicitly consents to marketing/advertising (e.g., ad tracking, remarketing)', 'custom-cookie-consent'); ?></li>
            </ul>

            <div class="cookie-documentation-important">
                <h4><?php _e('Important for Compliance:', 'custom-cookie-consent'); ?></h4>
                <p><?php _e('Each category is processed separately by the plugin. If a user only accepts analytics cookies but declines marketing cookies, the marketing-related features remain disabled. This granular approach is essential for GDPR compliance, which requires specific consent for each purpose.', 'custom-cookie-consent'); ?></p>
            </div>
        </div>

        <div class="cookie-documentation-section">
            <h3><?php _e('Google Consent Mode Integration', 'custom-cookie-consent'); ?></h3>
            <p><?php _e('This plugin correctly implements Google Consent Mode v2 with proper category separation:', 'custom-cookie-consent'); ?></p>

            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th><?php _e('Consent Category', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('Google Consent Mode Signals', 'custom-cookie-consent'); ?></th>
                        <th><?php _e('When Enabled', 'custom-cookie-consent'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php _e('Necessary', 'custom-cookie-consent'); ?></strong></td>
                        <td>security_storage</td>
                        <td><?php _e('Always enabled', 'custom-cookie-consent'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Analytics', 'custom-cookie-consent'); ?></strong></td>
                        <td>analytics_storage</td>
                        <td><?php _e('Only when analytics consent is given', 'custom-cookie-consent'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Functional', 'custom-cookie-consent'); ?></strong></td>
                        <td>functionality_storage, personalization_storage</td>
                        <td><?php _e('Only when functional consent is given', 'custom-cookie-consent'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Marketing', 'custom-cookie-consent'); ?></strong></td>
                        <td>ad_storage, ad_user_data, ad_personalization</td>
                        <td><?php _e('Only when marketing consent is explicitly given', 'custom-cookie-consent'); ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="cookie-documentation-warning">
                <h4><?php _e('Common Implementation Mistake:', 'custom-cookie-consent'); ?></h4>
                <p><?php _e('Many cookie consent solutions incorrectly enable ad_storage when only analytics consent is given. This plugin properly separates these consent types, ensuring that marketing cookies are only enabled when specifically authorized by the user.', 'custom-cookie-consent'); ?></p>
            </div>
        </div>
    </div>

    <div class="cookie-consent-admin-card">
        <h2><?php _e('Best Practices', 'custom-cookie-consent'); ?></h2>

        <div class="cookie-documentation-section">
            <h3><?php _e('Banner Configuration', 'custom-cookie-consent'); ?></h3>
            <ol>
                <li><strong><?php _e('Banner Visibility:', 'custom-cookie-consent'); ?></strong> <?php _e('Place the banner prominently at the bottom or center of the screen.', 'custom-cookie-consent'); ?></li>
                <li><strong><?php _e('Decline Button:', 'custom-cookie-consent'); ?></strong> <?php _e('Always include a visible "Decline All" button that\'s as prominent as the "Accept" button.', 'custom-cookie-consent'); ?></li>
                <li><strong><?php _e('Default State:', 'custom-cookie-consent'); ?></strong> <?php _e('Ensure all non-necessary cookie categories are unchecked by default (this is already configured correctly in the plugin).', 'custom-cookie-consent'); ?></li>
                <li><strong><?php _e('Cookie Settings Link:', 'custom-cookie-consent'); ?></strong> <?php _e('Add a permanent cookie settings link in your footer using the shortcode [cookie_settings].', 'custom-cookie-consent'); ?></li>
                <li><strong><?php _e('Banner Text:', 'custom-cookie-consent'); ?></strong> <?php _e('Clearly explain what each category of cookies does and how it affects the user\'s privacy.', 'custom-cookie-consent'); ?></li>
            </ol>
        </div>

        <div class="cookie-documentation-section">
            <h3><?php _e('Anti-Ad-Blocker Protection', 'custom-cookie-consent'); ?></h3>
            <p><?php _e('The Anti-Ad-Blocker Protection feature ensures your cookie consent banner remains visible and functional even when users have ad blockers installed:', 'custom-cookie-consent'); ?></p>

            <ul>
                <li><strong><?php _e('Purpose:', 'custom-cookie-consent'); ?></strong> <?php _e('Some ad blockers mistakenly block cookie banners, preventing users from expressing their consent preferences. This can lead to non-compliance with GDPR and ePrivacy regulations.', 'custom-cookie-consent'); ?></li>
                <li><strong><?php _e('How it works:', 'custom-cookie-consent'); ?></strong> <?php _e('When enabled, this feature uses advanced techniques to detect if ad blockers are interfering with your consent banner and deploys countermeasures to ensure the banner is displayed properly.', 'custom-cookie-consent'); ?></li>
                <li><strong><?php _e('GDPR Compliance:', 'custom-cookie-consent'); ?></strong> <?php _e('This feature maintains compliance by ensuring your visitors can always access consent options, while still respecting their chosen preferences.', 'custom-cookie-consent'); ?></li>
            </ul>

            <div class="cookie-documentation-tip">
                <h4><?php _e('Recommendation:', 'custom-cookie-consent'); ?></h4>
                <p><?php _e('It\'s recommended to enable this feature to ensure all visitors have the opportunity to manage their cookie preferences, which is required for compliance with privacy regulations. Ad blockers should not prevent users from making informed consent choices.', 'custom-cookie-consent'); ?></p>
            </div>
        </div>

        <div class="cookie-documentation-section">
            <h3><?php _e('Google Integration Setup', 'custom-cookie-consent'); ?></h3>
            <p><?php _e('For proper Google service integration:', 'custom-cookie-consent'); ?></p>

            <ol>
                <li><?php _e('Do NOT add Google Tag Manager code directly to your site. Instead, enter your GTM ID in the plugin settings to ensure proper consent handling.', 'custom-cookie-consent'); ?></li>
                <li><?php _e('If using Google Analytics, enable Google Site Kit integration in the Integration Settings section.', 'custom-cookie-consent'); ?></li>
                <li><?php _e('Configure the appropriate consent region (Norway, EEA, or Global) based on your audience in the Integration Settings.', 'custom-cookie-consent'); ?></li>
            </ol>

            <div class="cookie-documentation-tip">
                <h4><?php _e('Pro Tip:', 'custom-cookie-consent'); ?></h4>
                <p><?php _e('If your site has visitors from multiple EEA countries, not just Norway, select "EEA" as your region setting. This ensures that consent requirements are not inadvertently bypassed for users from other EU countries.', 'custom-cookie-consent'); ?></p>
            </div>
        </div>

        <div class="cookie-documentation-section">
            <h3><?php _e('Implementation Checklist', 'custom-cookie-consent'); ?></h3>
            <ul class="cookie-documentation-checklist">
                <li><?php _e('Configure the banner position and appearance in Banner Settings', 'custom-cookie-consent'); ?></li>
                <li><?php _e('Set up your Privacy Policy URL and Cookie Policy URL', 'custom-cookie-consent'); ?></li>
                <li><?php _e('Enable Anti-Ad-Blocker Protection to ensure the banner is displayed to all users', 'custom-cookie-consent'); ?></li>
                <li><?php _e('Configure appropriate region settings (Norway, EEA, or Global)', 'custom-cookie-consent'); ?></li>
                <li><?php _e('Add a cookie settings link to your site footer using the [cookie_settings] shortcode', 'custom-cookie-consent'); ?></li>
                <li><?php _e('Customize the text for each consent category to accurately describe what cookies are used for', 'custom-cookie-consent'); ?></li>
                <li><?php _e('Run a cookie scan to detect all cookies on your site', 'custom-cookie-consent'); ?></li>
                <li><?php _e('Categorize any uncategorized cookies detected by the scanner', 'custom-cookie-consent'); ?></li>
                <li><?php _e('Update your Cookie Policy to list all cookies and their purposes', 'custom-cookie-consent'); ?></li>
                <li><?php _e('Test your consent mechanism by accepting/declining different categories and verifying behavior', 'custom-cookie-consent'); ?></li>
            </ul>
        </div>
    </div>

    <div class="cookie-consent-admin-card">
        <h2><?php _e('Frequently Asked Questions', 'custom-cookie-consent'); ?></h2>

        <div class="cookie-documentation-section cookie-documentation-faq">
            <h3><?php _e('Why is separating analytics and marketing consent important?', 'custom-cookie-consent'); ?></h3>
            <p><?php _e('Under the GDPR, consent must be specific for each purpose. Many users are willing to accept analytics cookies to help site owners improve their services but don\'t want to be tracked for advertising purposes. Proper separation ensures users have granular control over their privacy, as required by law.', 'custom-cookie-consent'); ?></p>

            <h3><?php _e('What happens if a user only accepts analytics but not marketing?', 'custom-cookie-consent'); ?></h3>
            <p><?php _e('If a user accepts only analytics cookies, the plugin will enable analytics_storage but keep ad_storage, ad_user_data, and ad_personalization set to "denied" in Google Consent Mode. This means Google Analytics will collect data, but advertising features will remain disabled.', 'custom-cookie-consent'); ?></p>

            <h3><?php _e('What is the purpose of the region setting?', 'custom-cookie-consent'); ?></h3>
            <p><?php _e('The region setting defines where consent restrictions should apply. For example, if set to "NO", the default deny settings only apply to users in Norway. When set to "EEA", they apply to all European Economic Area countries. The "Global" setting applies these restrictions worldwide regardless of user location.', 'custom-cookie-consent'); ?></p>

            <h3><?php _e('Do I need to update my cookie policy with this plugin?', 'custom-cookie-consent'); ?></h3>
            <p><?php _e('Yes. You should update your cookie policy to reflect all cookies used on your site, their purposes, and how users can manage them. The plugin\'s cookie scanner can help identify all cookies that need to be documented.', 'custom-cookie-consent'); ?></p>

            <h3><?php _e('Is the Anti-Ad-Blocker feature compliant with privacy regulations?', 'custom-cookie-consent'); ?></h3>
            <p><?php _e('Yes. The Anti-Ad-Blocker feature is designed to be fully compliant with GDPR and other privacy regulations. It doesn\'t bypass user consent - it simply ensures that users can see the consent banner to make an informed choice. Once a user makes their choice (accept or reject), the plugin still honors those preferences exactly as configured.', 'custom-cookie-consent'); ?></p>
        </div>
    </div>
</div>

<style>
    .cookie-documentation-section {
        margin-bottom: 30px;
    }

    .cookie-documentation-important,
    .cookie-documentation-warning,
    .cookie-documentation-tip {
        padding: 15px 20px;
        border-radius: 5px;
        margin: 20px 0;
    }

    .cookie-documentation-important {
        background-color: #e0f3ff;
        border-left: 4px solid #007cba;
    }

    .cookie-documentation-warning {
        background-color: #fff8e5;
        border-left: 4px solid #ffb900;
    }

    .cookie-documentation-tip {
        background-color: #eefaed;
        border-left: 4px solid #46b450;
    }

    .cookie-documentation-important h4,
    .cookie-documentation-warning h4,
    .cookie-documentation-tip h4 {
        margin-top: 0;
        color: #333;
    }

    .cookie-documentation-checklist li {
        margin-bottom: 10px;
        padding-left: 30px;
        position: relative;
    }

    .cookie-documentation-checklist li:before {
        content: "✓";
        position: absolute;
        left: 0;
        color: #46b450;
        font-weight: bold;
    }

    .cookie-documentation-faq h3 {
        cursor: pointer;
        padding: 10px 15px;
        background-color: #f9f9f9;
        border-left: 4px solid #e3e3e3;
        margin: 15px 0 5px;
    }

    .cookie-documentation-faq h3:hover {
        background-color: #f0f0f0;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Simple accordion for FAQ items
        const faqHeadings = document.querySelectorAll('.cookie-documentation-faq h3');

        faqHeadings.forEach(heading => {
            const answer = heading.nextElementSibling;
            answer.style.display = 'none';

            heading.addEventListener('click', () => {
                if (answer.style.display === 'none') {
                    answer.style.display = 'block';
                } else {
                    answer.style.display = 'none';
                }
            });
        });
    });
</script>