<?php

/**
 * Admin Translations Template
 *
 * Text and translations settings page for the cookie consent plugin.
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
        <h1><?php _e('Text & Translations', 'custom-cookie-consent'); ?></h1>

        <div class="cookie-consent-header-actions">
            <a href="<?php echo admin_url('admin.php?page=custom-cookie-settings'); ?>" class="button">
                <?php _e('Back to Settings', 'custom-cookie-consent'); ?>
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
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-settings'); ?>">
            <?php _e('Settings', 'custom-cookie-consent'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-translations'); ?>" class="active">
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
        <h2><?php _e('Text Customization & Translations', 'custom-cookie-consent'); ?></h2>
        <p class="description"><?php _e('Customize all front-end text displayed to your visitors. This allows for complete translation of all user-facing text.', 'custom-cookie-consent'); ?></p>

        <div class="notice notice-info">
            <p><?php _e('You can change the plugin language (English or Norwegian) in', 'custom-cookie-consent'); ?> <a href="<?php echo admin_url('admin.php?page=custom-cookie-settings'); ?>"><?php _e('Settings', 'custom-cookie-consent'); ?></a>. <?php _e('This controls both the admin interface language and default frontend text.', 'custom-cookie-consent'); ?></p>
        </div>

        <div class="translations-search-box">
            <input type="text" id="translations-search" placeholder="<?php _e('Search for settings...', 'custom-cookie-consent'); ?>" class="regular-text">
            <span class="dashicons dashicons-search"></span>
        </div>

        <div class="cookie-consent-tabs">
            <nav class="cookie-consent-tab-nav">
                <a href="#banner-section" class="tab-link active" data-tab="banner-section">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php _e('Banner Text', 'custom-cookie-consent'); ?>
                </a>
                <a href="#categories-section" class="tab-link" data-tab="categories-section">
                    <span class="dashicons dashicons-category"></span>
                    <?php _e('Cookie Categories', 'custom-cookie-consent'); ?>
                </a>
                <a href="#consent-data-section" class="tab-link" data-tab="consent-data-section">
                    <span class="dashicons dashicons-id"></span>
                    <?php _e('Consent Data Display', 'custom-cookie-consent'); ?>
                </a>
                <a href="#buttons-section" class="tab-link" data-tab="buttons-section">
                    <span class="dashicons dashicons-button"></span>
                    <?php _e('Buttons', 'custom-cookie-consent'); ?>
                </a>
                <a href="#labels-section" class="tab-link" data-tab="labels-section">
                    <span class="dashicons dashicons-tag"></span>
                    <?php _e('Labels & Messages', 'custom-cookie-consent'); ?>
                </a>
            </nav>

            <form class="cookie-consent-settings-form js-cookie-settings-form" method="post" action="">
                <!-- Banner Section -->
                <div id="banner-section" class="tab-content active">
                    <div class="settings-section">
                        <h3>
                            <span class="dashicons dashicons-megaphone"></span>
                            <?php _e('Main Banner Text', 'custom-cookie-consent'); ?>
                        </h3>
                        <p class="section-description"><?php _e('The primary text shown on your cookie consent banner.', 'custom-cookie-consent'); ?></p>

                        <div class="form-field">
                            <label for="banner_title"><?php _e('Banner Title', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="banner_title" name="banner_title" value="<?php echo esc_attr($settings['banner_title'] ?? ''); ?>" class="regular-text">
                            <p class="field-description"><?php _e('The headline of your cookie consent banner.', 'custom-cookie-consent'); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="banner_text"><?php _e('Banner Text', 'custom-cookie-consent'); ?></label>
                            <textarea id="banner_text" name="banner_text" rows="4" class="large-text"><?php echo esc_textarea($settings['banner_text'] ?? ''); ?></textarea>
                            <p class="field-description"><?php _e('The main explanatory text of your cookie banner.', 'custom-cookie-consent'); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="privacy_text"><?php _e('Privacy Link Text', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="privacy_text" name="privacy_text" value="<?php echo esc_attr($settings['privacy_text'] ?? ''); ?>" class="regular-text">
                        </div>

                        <div class="form-field">
                            <label for="cookie_policy_text"><?php _e('Cookie Policy Link Text', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="cookie_policy_text" name="cookie_policy_text" value="<?php echo esc_attr($settings['cookie_policy_text'] ?? ''); ?>" class="regular-text">
                            <p class="field-description"><?php _e('The text for the cookie policy link in the banner.', 'custom-cookie-consent'); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="close_button_text"><?php _e('Close Button Text', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="close_button_text" name="close_button_text" value="<?php echo esc_attr($settings['close_button_text'] ?? ''); ?>" class="regular-text">
                        </div>

                        <div class="form-field">
                            <label for="close_button_aria_label"><?php _e('Close Button Aria Label', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="close_button_aria_label" name="close_button_aria_label" value="<?php echo esc_attr($settings['close_button_aria_label'] ?? ''); ?>" class="regular-text">
                            <p class="field-description"><?php _e('Accessibility text for screen readers.', 'custom-cookie-consent'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Categories Section -->
                <div id="categories-section" class="tab-content">
                    <div class="settings-section">
                        <h3>
                            <span class="dashicons dashicons-shield"></span>
                            <?php _e('Necessary Cookies', 'custom-cookie-consent'); ?>
                        </h3>

                        <div class="form-field">
                            <label for="necessary_title"><?php _e('Necessary Category Title', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="necessary_title" name="necessary_title" value="<?php echo esc_attr($settings['necessary_title'] ?? 'Nødvendige'); ?>" class="regular-text">
                        </div>

                        <div class="form-field">
                            <label for="necessary_description"><?php _e('Necessary Category Description', 'custom-cookie-consent'); ?></label>
                            <textarea id="necessary_description" name="necessary_description" rows="3" class="large-text"><?php echo esc_textarea($settings['necessary_description'] ?? 'Disse informasjonskapslene er nødvendige for at nettstedet skal fungere og kan ikke deaktiveres.'); ?></textarea>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3>
                            <span class="dashicons dashicons-chart-bar"></span>
                            <?php _e('Analytics Cookies', 'custom-cookie-consent'); ?>
                        </h3>

                        <div class="form-field">
                            <label for="analytics_title"><?php _e('Analytics Category Title', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="analytics_title" name="analytics_title" value="<?php echo esc_attr($settings['analytics_title'] ?? 'Analyse'); ?>" class="regular-text">
                        </div>

                        <div class="form-field">
                            <label for="analytics_description"><?php _e('Analytics Category Description', 'custom-cookie-consent'); ?></label>
                            <textarea id="analytics_description" name="analytics_description" rows="3" class="large-text"><?php echo esc_textarea($settings['analytics_description'] ?? 'Disse informasjonskapslene hjelper oss å forstå hvordan besøkende bruker nettstedet.'); ?></textarea>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3>
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php _e('Functional Cookies', 'custom-cookie-consent'); ?>
                        </h3>

                        <div class="form-field">
                            <label for="functional_title"><?php _e('Functional Category Title', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="functional_title" name="functional_title" value="<?php echo esc_attr($settings['functional_title'] ?? 'Funksjonell'); ?>" class="regular-text">
                        </div>

                        <div class="form-field">
                            <label for="functional_description"><?php _e('Functional Category Description', 'custom-cookie-consent'); ?></label>
                            <textarea id="functional_description" name="functional_description" rows="3" class="large-text"><?php echo esc_textarea($settings['functional_description'] ?? 'Disse informasjonskapslene gjør at nettstedet kan gi forbedret funksjonalitet og personlig tilpasning.'); ?></textarea>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3>
                            <span class="dashicons dashicons-megaphone"></span>
                            <?php _e('Marketing Cookies', 'custom-cookie-consent'); ?>
                        </h3>

                        <div class="form-field">
                            <label for="marketing_title"><?php _e('Marketing Category Title', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="marketing_title" name="marketing_title" value="<?php echo esc_attr($settings['marketing_title'] ?? 'Markedsføring'); ?>" class="regular-text">
                        </div>

                        <div class="form-field">
                            <label for="marketing_description"><?php _e('Marketing Category Description', 'custom-cookie-consent'); ?></label>
                            <textarea id="marketing_description" name="marketing_description" rows="3" class="large-text"><?php echo esc_textarea($settings['marketing_description'] ?? 'Disse informasjonskapslene brukes til å spore besøkende på tvers av nettsteder for å vise relevante annonser.'); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Consent Data Section -->
                <div id="consent-data-section" class="tab-content">
                    <div class="settings-section">
                        <h3>
                            <span class="dashicons dashicons-id"></span>
                            <?php _e('Consent Data Headings', 'custom-cookie-consent'); ?>
                        </h3>

                        <div class="form-field">
                            <label for="consent_choices_heading"><?php _e('Consent Choices Heading', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="consent_choices_heading" name="consent_choices_heading" value="<?php echo esc_attr($settings['consent_choices_heading'] ?? 'Dine samtykkevalg'); ?>" class="regular-text">
                        </div>

                        <div class="form-field">
                            <label for="active_cookies_heading"><?php _e('Active Cookies Heading', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="active_cookies_heading" name="active_cookies_heading" value="<?php echo esc_attr($settings['active_cookies_heading'] ?? 'Aktive informasjonskapsler:'); ?>" class="regular-text">
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3>
                            <span class="dashicons dashicons-info"></span>
                            <?php _e('Status Labels', 'custom-cookie-consent'); ?>
                        </h3>

                        <div class="form-field">
                            <label for="consent_status_accepted"><?php _e('Status Accepted Text', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="consent_status_accepted" name="consent_status_accepted" value="<?php echo esc_attr($settings['consent_status_accepted'] ?? 'Godtatt'); ?>" class="regular-text">
                        </div>

                        <div class="form-field">
                            <label for="consent_status_declined"><?php _e('Status Declined Text', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="consent_status_declined" name="consent_status_declined" value="<?php echo esc_attr($settings['consent_status_declined'] ?? 'Avslått'); ?>" class="regular-text">
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3>
                            <span class="dashicons dashicons-info-outline"></span>
                            <?php _e('Cookie Information Labels', 'custom-cookie-consent'); ?>
                        </h3>

                        <div class="form-field">
                            <label for="cookie_category_label"><?php _e('Cookie Category Label', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="cookie_category_label" name="cookie_category_label" value="<?php echo esc_attr($settings['cookie_category_label'] ?? 'Kategori:'); ?>" class="regular-text">
                        </div>

                        <div class="form-field">
                            <label for="cookie_purpose_label"><?php _e('Cookie Purpose Label', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="cookie_purpose_label" name="cookie_purpose_label" value="<?php echo esc_attr($settings['cookie_purpose_label'] ?? 'Formål:'); ?>" class="regular-text">
                        </div>

                        <div class="form-field">
                            <label for="cookie_expiry_label"><?php _e('Cookie Expiry Label', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="cookie_expiry_label" name="cookie_expiry_label" value="<?php echo esc_attr($settings['cookie_expiry_label'] ?? 'Utløper:'); ?>" class="regular-text">
                        </div>
                    </div>
                </div>

                <!-- Buttons Section -->
                <div id="buttons-section" class="tab-content">
                    <div class="settings-section">
                        <h3>
                            <span class="dashicons dashicons-button"></span>
                            <?php _e('Banner Button Text', 'custom-cookie-consent'); ?>
                        </h3>

                        <div class="form-field form-buttons-preview">
                            <div class="button-preview accept-button-preview"><?php echo esc_html($settings['accept_button'] ?? 'Godta alle'); ?></div>
                            <div class="button-preview save-button-preview"><?php echo esc_html($settings['save_button'] ?? 'Lagre preferanser'); ?></div>
                            <div class="button-preview decline-button-preview"><?php echo esc_html($settings['decline_button'] ?? 'Avslå alle'); ?></div>
                        </div>

                        <div class="form-field">
                            <label for="accept_button"><?php _e('Accept Button Text', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="accept_button" name="accept_button" value="<?php echo esc_attr($settings['accept_button'] ?? 'Godta alle'); ?>" class="regular-text">
                        </div>

                        <div class="form-field">
                            <label for="decline_button"><?php _e('Decline Button Text', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="decline_button" name="decline_button" value="<?php echo esc_attr($settings['decline_button'] ?? 'Avslå alle'); ?>" class="regular-text">
                        </div>

                        <div class="form-field">
                            <label for="save_button"><?php _e('Save Preferences Button Text', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="save_button" name="save_button" value="<?php echo esc_attr($settings['save_button'] ?? 'Lagre preferanser'); ?>" class="regular-text">
                        </div>

                        <div class="form-field">
                            <label for="change_settings_button"><?php _e('Change Settings Button', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="change_settings_button" name="change_settings_button" value="<?php echo esc_attr($settings['change_settings_button'] ?? 'Endre samtykkeinnstillinger'); ?>" class="regular-text">
                            <p class="field-description"><?php _e('Text for the link that reopens the consent banner.', 'custom-cookie-consent'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Labels Section -->
                <div id="labels-section" class="tab-content">
                    <div class="settings-section">
                        <h3>
                            <span class="dashicons dashicons-tag"></span>
                            <?php _e('Additional Labels & Messages', 'custom-cookie-consent'); ?>
                        </h3>

                        <div class="form-field">
                            <label for="sources_label"><?php _e('Sources Label', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="sources_label" name="sources_label" value="<?php echo esc_attr($settings['sources_label'] ?? 'Brukes av:'); ?>" class="regular-text">
                            <p class="field-description"><?php _e('Label for showing which services use a particular cookie.', 'custom-cookie-consent'); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="consent_last_updated"><?php _e('Last Updated Label', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="consent_last_updated" name="consent_last_updated" value="<?php echo esc_attr($settings['consent_last_updated'] ?? 'Sist oppdatert:'); ?>" class="regular-text">
                        </div>

                        <div class="form-field">
                            <label for="no_cookies_message"><?php _e('No Cookies Message', 'custom-cookie-consent'); ?></label>
                            <input type="text" id="no_cookies_message" name="no_cookies_message" value="<?php echo esc_attr($settings['no_cookies_message'] ?? 'Ingen aktive informasjonskapsler funnet.'); ?>" class="regular-text">
                            <p class="field-description"><?php _e('Message shown when no cookies are detected.', 'custom-cookie-consent'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="submit-container">
                    <input type="submit" name="submit" class="button button-primary cookie-consent-submit" value="<?php _e('Save All Translations', 'custom-cookie-consent'); ?>">
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Enhanced styling for Translations page */
    .cookie-consent-tabs {
        margin-top: 20px;
    }

    .cookie-consent-tab-nav {
        display: flex;
        border-bottom: 1px solid #ccc;
        margin-bottom: 20px;
        gap: 5px;
    }

    .tab-link {
        padding: 10px 15px;
        border: 1px solid #ccc;
        border-bottom: none;
        border-radius: 3px 3px 0 0;
        background: #f7f7f7;
        text-decoration: none;
        color: #555;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
        position: relative;
        bottom: -1px;
    }

    .tab-link.active {
        background: #fff;
        border-bottom: 1px solid #fff;
        color: #0073aa;
    }

    .tab-link:hover {
        background: #fafafa;
        color: #0073aa;
    }

    .tab-link .dashicons {
        font-size: 18px;
    }

    .tab-content {
        display: none;
        padding: 15px;
        background: #fff;
        border: 1px solid #ccc;
        border-top: none;
        margin-top: -1px;
    }

    .tab-content.active {
        display: block;
    }

    .settings-section {
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }

    .settings-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .settings-section h3 {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #23282d;
        font-size: 1.1em;
        margin-top: 0;
    }

    .form-field {
        margin-bottom: 15px;
        padding-left: 15px;
    }

    .form-field label {
        font-weight: 500;
        margin-bottom: 5px;
        display: block;
    }

    .form-field .field-description,
    .section-description {
        color: #666;
        font-style: italic;
        margin-top: 5px;
        font-size: 0.9em;
    }

    .submit-container {
        margin-top: 20px;
        padding: 15px;
        background: #f9f9f9;
        border-top: 1px solid #eee;
        text-align: right;
    }

    .translations-search-box {
        margin-bottom: 20px;
        position: relative;
    }

    .translations-search-box input {
        width: 100%;
        padding: 8px 12px 8px 35px;
    }

    .translations-search-box .dashicons {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
    }

    /* Button preview */
    .form-buttons-preview {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }

    .button-preview {
        padding: 8px 16px;
        border-radius: 4px;
        font-size: 14px;
        display: inline-block;
        text-align: center;
    }

    .accept-button-preview {
        background: #4C4CFF;
        color: white;
    }

    .decline-button-preview {
        background: #f5f5f5;
        color: #333;
    }

    .save-button-preview {
        background: #e0e0fd;
        color: #333;
        border: 1px solid #4C4CFF;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Tab functionality
        $('.tab-link').on('click', function(e) {
            e.preventDefault();
            var target = $(this).data('tab');

            // Update active tab
            $('.tab-link').removeClass('active');
            $(this).addClass('active');

            // Show target content
            $('.tab-content').removeClass('active');
            $('#' + target).addClass('active');
        });

        // Search functionality
        $('#translations-search').on('input', function() {
            var searchValue = $(this).val().toLowerCase();

            if (searchValue.length > 2) {
                // If there is a search term with at least 3 characters
                $('.form-field').each(function() {
                    var fieldText = $(this).text().toLowerCase();
                    var fieldInput = $(this).find('input, textarea').val().toLowerCase();

                    if (fieldText.includes(searchValue) || fieldInput.includes(searchValue)) {
                        $(this).show();

                        // Show parent sections and tabs
                        var parentSection = $(this).closest('.settings-section');
                        var parentTab = $(this).closest('.tab-content');

                        parentSection.show();
                        parentTab.addClass('active');
                        $('.tab-link[data-tab="' + parentTab.attr('id') + '"]').addClass('active');
                    } else {
                        $(this).hide();
                    }
                });

                // Hide empty sections
                $('.settings-section').each(function() {
                    var visibleFields = $(this).find('.form-field:visible').length;
                    if (visibleFields === 0) {
                        $(this).hide();
                    }
                });

                // Show only tabs with visible content
                $('.tab-content').each(function() {
                    if ($(this).find('.form-field:visible').length === 0) {
                        $(this).removeClass('active');
                        $('.tab-link[data-tab="' + $(this).attr('id') + '"]').removeClass('active');
                    }
                });
            } else {
                // If search is cleared or too short, restore default view
                $('.form-field, .settings-section').show();
                $('.tab-content').removeClass('active');
                $('.tab-link').removeClass('active');
                $('#banner-section').addClass('active');
                $('.tab-link[data-tab="banner-section"]').addClass('active');
            }
        });

        // Live update button preview text
        $('#accept_button').on('input', function() {
            $('.accept-button-preview').text($(this).val());
        });

        $('#save_button').on('input', function() {
            $('.save-button-preview').text($(this).val());
        });

        $('#decline_button').on('input', function() {
            $('.decline-button-preview').text($(this).val());
        });
    });
</script>