<?php

/**
 * Admin Design Template
 *
 * Design customization page for the cookie consent plugin.
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
        <h1><?php _e('Cookie Consent Styling', 'custom-cookie-consent'); ?></h1>

        <div class="cookie-consent-header-actions">
            <a href="<?php echo admin_url('admin.php?page=custom-cookie-translations'); ?>" class="button">
                <?php _e('Text & Translations', 'custom-cookie-consent'); ?>
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
        <a href="<?php echo admin_url('admin.php?page=custom-cookie-design'); ?>" class="active">
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
        <h2><?php _e('Styling Customization', 'custom-cookie-consent'); ?></h2>
        <p class="description"><?php _e('Customize the appearance of your cookie consent banner. All customizations comply with WCAG 2.2 Level AA accessibility requirements.', 'custom-cookie-consent'); ?></p>

        <form class="cookie-consent-settings-form js-design-settings-form" method="post" action="">
            <?php wp_nonce_field('cookie_design_nonce', 'nonce'); ?>

            <div class="cookie-consent-tabs">
                <nav class="cookie-consent-tab-nav">
                    <a href="#general-section" class="tab-link active" data-tab="general-section">
                        <span class="dashicons dashicons-admin-appearance"></span>
                        <?php _e('General', 'custom-cookie-consent'); ?>
                    </a>
                    <a href="#colors-section" class="tab-link" data-tab="colors-section">
                        <span class="dashicons dashicons-art"></span>
                        <?php _e('Colors', 'custom-cookie-consent'); ?>
                    </a>
                    <a href="#buttons-section" class="tab-link" data-tab="buttons-section">
                        <span class="dashicons dashicons-button"></span>
                        <?php _e('Buttons', 'custom-cookie-consent'); ?>
                    </a>
                    <a href="#typography-section" class="tab-link" data-tab="typography-section">
                        <span class="dashicons dashicons-editor-textcolor"></span>
                        <?php _e('Typography', 'custom-cookie-consent'); ?>
                    </a>
                    <a href="#spacing-section" class="tab-link" data-tab="spacing-section">
                        <span class="dashicons dashicons-editor-expand"></span>
                        <?php _e('Spacing', 'custom-cookie-consent'); ?>
                    </a>
                    <a href="#animation-section" class="tab-link" data-tab="animation-section">
                        <span class="dashicons dashicons-slides"></span>
                        <?php _e('Animation', 'custom-cookie-consent'); ?>
                    </a>
                    <a href="#advanced-section" class="tab-link" data-tab="advanced-section">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('Advanced', 'custom-cookie-consent'); ?>
                    </a>
                </nav>

                <!-- General Section -->
                <div id="general-section" class="tab-content active">
                    <div class="form-field">
                        <label>
                            <input type="checkbox" name="inherit_theme" value="1" <?php checked($design['inherit_theme']); ?>>
                            <?php _e('Inherit Styling from Theme', 'custom-cookie-consent'); ?>
                        </label>
                        <p class="description"><?php _e('When enabled, the banner will attempt to match your theme\'s styling (font family, colors, etc.). Specific customizations below will override this.', 'custom-cookie-consent'); ?></p>
                    </div>

                    <div class="form-field">
                        <label for="banner_position"><?php _e('Banner Position', 'custom-cookie-consent'); ?></label>
                        <select id="banner_position" name="banner_position">
                            <option value="bottom" <?php selected($design['banner_position'], 'bottom'); ?>><?php _e('Bottom', 'custom-cookie-consent'); ?></option>
                            <option value="top" <?php selected($design['banner_position'], 'top'); ?>><?php _e('Top', 'custom-cookie-consent'); ?></option>
                            <option value="bottom-left" <?php selected($design['banner_position'], 'bottom-left'); ?>><?php _e('Bottom Left', 'custom-cookie-consent'); ?></option>
                            <option value="bottom-right" <?php selected($design['banner_position'], 'bottom-right'); ?>><?php _e('Bottom Right', 'custom-cookie-consent'); ?></option>
                            <option value="center" <?php selected($design['banner_position'], 'center'); ?>><?php _e('Center (Modal)', 'custom-cookie-consent'); ?></option>
                        </select>
                        <p class="description"><?php _e('Position of the cookie consent banner on your website.', 'custom-cookie-consent'); ?></p>
                    </div>

                    <div class="form-field">
                        <label for="banner_layout"><?php _e('Banner Layout', 'custom-cookie-consent'); ?></label>
                        <select id="banner_layout" name="banner_layout">
                            <option value="bar" <?php selected($design['banner_layout'], 'bar'); ?>><?php _e('Full-width Bar', 'custom-cookie-consent'); ?></option>
                            <option value="card" <?php selected($design['banner_layout'], 'card'); ?>><?php _e('Floating Card', 'custom-cookie-consent'); ?></option>
                            <option value="modal" <?php selected($design['banner_layout'], 'modal'); ?>><?php _e('Centered Modal', 'custom-cookie-consent'); ?></option>
                        </select>
                        <p class="description"><?php _e('Layout style for the cookie consent banner.', 'custom-cookie-consent'); ?></p>
                    </div>

                    <div class="form-field banner-preview-container">
                        <h3><?php _e('Preview', 'custom-cookie-consent'); ?></h3>
                        <div class="banner-preview" id="banner-preview">
                            <div class="banner-preview-inner">
                                <div class="banner-preview-content">
                                    <h4><?php echo esc_html($settings['banner_title'] ?? __('We use cookies', 'custom-cookie-consent')); ?></h4>
                                    <p><?php echo esc_html($settings['banner_text'] ?? __('We use cookies to improve your experience, personalize content and analyze our traffic.', 'custom-cookie-consent')); ?></p>
                                </div>
                                <div class="banner-preview-buttons">
                                    <button type="button" class="preview-button preview-accept"><?php echo esc_html($settings['accept_button'] ?? __('Accept All', 'custom-cookie-consent')); ?></button>
                                    <button type="button" class="preview-button preview-save"><?php echo esc_html($settings['save_button'] ?? __('Save Preferences', 'custom-cookie-consent')); ?></button>
                                    <button type="button" class="preview-button preview-decline"><?php echo esc_html($settings['decline_button'] ?? __('Decline All', 'custom-cookie-consent')); ?></button>
                                </div>
                            </div>
                        </div>
                        <p class="description"><?php _e('This is a preview of how your cookie banner might look with the current settings. The actual banner may vary depending on your theme and other settings.', 'custom-cookie-consent'); ?></p>
                    </div>
                </div>

                <!-- Colors Section -->
                <div id="colors-section" class="tab-content">
                    <h3><?php _e('Banner Colors', 'custom-cookie-consent'); ?></h3>

                    <div class="form-field">
                        <label for="banner_background_color"><?php _e('Background Color', 'custom-cookie-consent'); ?></label>
                        <input type="color" id="banner_background_color" name="banner_background_color" value="<?php echo esc_attr($design['banner_background_color']); ?>">
                        <input type="text" class="color-text-input" data-color-input="banner_background_color" value="<?php echo esc_attr($design['banner_background_color']); ?>">
                    </div>

                    <div class="form-field">
                        <label for="banner_text_color"><?php _e('Text Color', 'custom-cookie-consent'); ?></label>
                        <input type="color" id="banner_text_color" name="banner_text_color" value="<?php echo esc_attr($design['banner_text_color']); ?>">
                        <input type="text" class="color-text-input" data-color-input="banner_text_color" value="<?php echo esc_attr($design['banner_text_color']); ?>">
                        <p class="description"><?php _e('Text color for the banner content.', 'custom-cookie-consent'); ?></p>
                    </div>

                    <div class="form-field">
                        <label for="banner_border_color"><?php _e('Border Color', 'custom-cookie-consent'); ?></label>
                        <input type="color" id="banner_border_color" name="banner_border_color" value="<?php echo esc_attr($design['banner_border_color']); ?>">
                        <input type="text" class="color-text-input" data-color-input="banner_border_color" value="<?php echo esc_attr($design['banner_border_color']); ?>">
                    </div>

                    <div class="color-accessibility-notice notice notice-info inline">
                        <p><span class="dashicons dashicons-universal-access"></span> <?php _e('For WCAG 2.2 Level AA compliance, text color should have at least a 4.5:1 contrast ratio with the background.', 'custom-cookie-consent'); ?> <span id="contrast-status"></span></p>
                    </div>
                </div>

                <!-- Buttons Section -->
                <div id="buttons-section" class="tab-content">
                    <div class="form-field form-buttons-preview">
                        <div class="button-preview accept-button-preview" id="accept-button-preview"><?php echo esc_html($settings['accept_button'] ?? __('Accept All', 'custom-cookie-consent')); ?></div>
                        <div class="button-preview save-button-preview" id="save-button-preview"><?php echo esc_html($settings['save_button'] ?? __('Save Preferences', 'custom-cookie-consent')); ?></div>
                        <div class="button-preview decline-button-preview" id="decline-button-preview"><?php echo esc_html($settings['decline_button'] ?? __('Decline All', 'custom-cookie-consent')); ?></div>
                    </div>

                    <h3><?php _e('Accept Button', 'custom-cookie-consent'); ?></h3>

                    <div class="form-field">
                        <label for="accept_button_background"><?php _e('Background Color', 'custom-cookie-consent'); ?></label>
                        <input type="color" id="accept_button_background" name="accept_button_background" value="<?php echo esc_attr($design['accept_button_background']); ?>">
                        <input type="text" class="color-text-input" data-color-input="accept_button_background" value="<?php echo esc_attr($design['accept_button_background']); ?>">
                    </div>

                    <div class="form-field">
                        <label for="accept_button_text_color"><?php _e('Text Color', 'custom-cookie-consent'); ?></label>
                        <input type="color" id="accept_button_text_color" name="accept_button_text_color" value="<?php echo esc_attr($design['accept_button_text_color']); ?>">
                        <input type="text" class="color-text-input" data-color-input="accept_button_text_color" value="<?php echo esc_attr($design['accept_button_text_color']); ?>">
                    </div>

                    <div class="form-field">
                        <label for="accept_button_border_color"><?php _e('Border Color', 'custom-cookie-consent'); ?></label>
                        <input type="color" id="accept_button_border_color" name="accept_button_border_color" value="<?php echo esc_attr($design['accept_button_border_color']); ?>">
                        <input type="text" class="color-text-input" data-color-input="accept_button_border_color" value="<?php echo esc_attr($design['accept_button_border_color']); ?>">
                    </div>

                    <div class="button-accessibility-notice notice notice-info inline">
                        <p><span class="dashicons dashicons-universal-access"></span> <?php _e('For WCAG 2.2 Level AA compliance, button text should have at least a 4.5:1 contrast ratio with its background.', 'custom-cookie-consent'); ?> <span id="accept-button-contrast-status"></span></p>
                    </div>

                    <h3><?php _e('Save Preferences Button', 'custom-cookie-consent'); ?></h3>

                    <div class="form-field">
                        <label for="save_button_background"><?php _e('Background Color', 'custom-cookie-consent'); ?></label>
                        <input type="color" id="save_button_background" name="save_button_background" value="<?php echo esc_attr($design['save_button_background']); ?>">
                        <input type="text" class="color-text-input" data-color-input="save_button_background" value="<?php echo esc_attr($design['save_button_background']); ?>">
                    </div>

                    <div class="form-field">
                        <label for="save_button_text_color"><?php _e('Text Color', 'custom-cookie-consent'); ?></label>
                        <input type="color" id="save_button_text_color" name="save_button_text_color" value="<?php echo esc_attr($design['save_button_text_color']); ?>">
                        <input type="text" class="color-text-input" data-color-input="save_button_text_color" value="<?php echo esc_attr($design['save_button_text_color']); ?>">
                    </div>

                    <div class="form-field">
                        <label for="save_button_border_color"><?php _e('Border Color', 'custom-cookie-consent'); ?></label>
                        <input type="color" id="save_button_border_color" name="save_button_border_color" value="<?php echo esc_attr($design['save_button_border_color']); ?>">
                        <input type="text" class="color-text-input" data-color-input="save_button_border_color" value="<?php echo esc_attr($design['save_button_border_color']); ?>">
                    </div>

                    <div class="button-accessibility-notice notice notice-info inline">
                        <p><span class="dashicons dashicons-universal-access"></span> <?php _e('For WCAG 2.2 Level AA compliance, button text should have at least a 4.5:1 contrast ratio with its background.', 'custom-cookie-consent'); ?> <span id="save-button-contrast-status"></span></p>
                    </div>

                    <h3><?php _e('Decline Button', 'custom-cookie-consent'); ?></h3>

                    <div class="form-field">
                        <label for="decline_button_background"><?php _e('Background Color', 'custom-cookie-consent'); ?></label>
                        <input type="color" id="decline_button_background" name="decline_button_background" value="<?php echo esc_attr($design['decline_button_background']); ?>">
                        <input type="text" class="color-text-input" data-color-input="decline_button_background" value="<?php echo esc_attr($design['decline_button_background']); ?>">
                    </div>

                    <div class="form-field">
                        <label for="decline_button_text_color"><?php _e('Text Color', 'custom-cookie-consent'); ?></label>
                        <input type="color" id="decline_button_text_color" name="decline_button_text_color" value="<?php echo esc_attr($design['decline_button_text_color']); ?>">
                        <input type="text" class="color-text-input" data-color-input="decline_button_text_color" value="<?php echo esc_attr($design['decline_button_text_color']); ?>">
                    </div>

                    <div class="form-field">
                        <label for="decline_button_border_color"><?php _e('Border Color', 'custom-cookie-consent'); ?></label>
                        <input type="color" id="decline_button_border_color" name="decline_button_border_color" value="<?php echo esc_attr($design['decline_button_border_color']); ?>">
                        <input type="text" class="color-text-input" data-color-input="decline_button_border_color" value="<?php echo esc_attr($design['decline_button_border_color']); ?>">
                    </div>

                    <div class="button-accessibility-notice notice notice-info inline">
                        <p><span class="dashicons dashicons-universal-access"></span> <?php _e('For WCAG 2.2 Level AA compliance, button text should have at least a 4.5:1 contrast ratio with its background.', 'custom-cookie-consent'); ?> <span id="decline-button-contrast-status"></span></p>
                    </div>
                </div>

                <!-- Typography Section -->
                <div id="typography-section" class="tab-content">
                    <div class="form-field">
                        <label for="font_family"><?php _e('Font Family', 'custom-cookie-consent'); ?></label>
                        <select id="font_family" name="font_family">
                            <option value="inherit" <?php selected($design['font_family'], 'inherit'); ?>><?php _e('Inherit from Theme', 'custom-cookie-consent'); ?></option>
                            <option value="system-ui" <?php selected($design['font_family'], 'system-ui'); ?>><?php _e('System UI', 'custom-cookie-consent'); ?></option>
                            <option value="Arial, sans-serif" <?php selected($design['font_family'], 'Arial, sans-serif'); ?>><?php _e('Arial', 'custom-cookie-consent'); ?></option>
                            <option value="Helvetica, Arial, sans-serif" <?php selected($design['font_family'], 'Helvetica, Arial, sans-serif'); ?>><?php _e('Helvetica', 'custom-cookie-consent'); ?></option>
                            <option value="'Open Sans', sans-serif" <?php selected($design['font_family'], "'Open Sans', sans-serif"); ?>><?php _e('Open Sans', 'custom-cookie-consent'); ?></option>
                            <option value="'Roboto', sans-serif" <?php selected($design['font_family'], "'Roboto', sans-serif"); ?>><?php _e('Roboto', 'custom-cookie-consent'); ?></option>
                            <option value="Georgia, serif" <?php selected($design['font_family'], 'Georgia, serif'); ?>><?php _e('Georgia', 'custom-cookie-consent'); ?></option>
                            <option value="'Times New Roman', Times, serif" <?php selected($design['font_family'], "'Times New Roman', Times, serif"); ?>><?php _e('Times New Roman', 'custom-cookie-consent'); ?></option>
                        </select>
                        <p class="description"><?php _e('Font family for the cookie consent banner text.', 'custom-cookie-consent'); ?></p>
                    </div>

                    <div class="form-field">
                        <label for="font_size"><?php _e('Font Size', 'custom-cookie-consent'); ?></label>
                        <select id="font_size" name="font_size">
                            <option value="12px" <?php selected($design['font_size'], '12px'); ?>><?php _e('Small (12px)', 'custom-cookie-consent'); ?></option>
                            <option value="14px" <?php selected($design['font_size'], '14px'); ?>><?php _e('Medium (14px)', 'custom-cookie-consent'); ?></option>
                            <option value="16px" <?php selected($design['font_size'], '16px'); ?>><?php _e('Large (16px)', 'custom-cookie-consent'); ?></option>
                            <option value="18px" <?php selected($design['font_size'], '18px'); ?>><?php _e('Extra Large (18px)', 'custom-cookie-consent'); ?></option>
                        </select>
                        <p class="description"><?php _e('Base font size for the cookie consent banner. Headings will be proportionally larger.', 'custom-cookie-consent'); ?></p>
                    </div>

                    <div class="form-field">
                        <label for="font_weight"><?php _e('Font Weight', 'custom-cookie-consent'); ?></label>
                        <select id="font_weight" name="font_weight">
                            <option value="normal" <?php selected($design['font_weight'], 'normal'); ?>><?php _e('Normal', 'custom-cookie-consent'); ?></option>
                            <option value="bold" <?php selected($design['font_weight'], 'bold'); ?>><?php _e('Bold', 'custom-cookie-consent'); ?></option>
                        </select>
                        <p class="description"><?php _e('Font weight for the banner text.', 'custom-cookie-consent'); ?></p>
                    </div>

                    <div class="typography-accessibility-notice notice notice-info inline">
                        <p><span class="dashicons dashicons-universal-access"></span> <?php _e('Font size will automatically scale based on user preferences, respecting browser accessibility settings.', 'custom-cookie-consent'); ?></p>
                    </div>
                </div>

                <!-- Spacing Section -->
                <div id="spacing-section" class="tab-content">
                    <div class="form-field">
                        <label for="banner_padding"><?php _e('Banner Padding', 'custom-cookie-consent'); ?></label>
                        <select id="banner_padding" name="banner_padding">
                            <option value="10px" <?php selected($design['banner_padding'], '10px'); ?>><?php _e('Small (10px)', 'custom-cookie-consent'); ?></option>
                            <option value="15px" <?php selected($design['banner_padding'], '15px'); ?>><?php _e('Medium (15px)', 'custom-cookie-consent'); ?></option>
                            <option value="20px" <?php selected($design['banner_padding'], '20px'); ?>><?php _e('Large (20px)', 'custom-cookie-consent'); ?></option>
                            <option value="30px" <?php selected($design['banner_padding'], '30px'); ?>><?php _e('Extra Large (30px)', 'custom-cookie-consent'); ?></option>
                        </select>
                        <p class="description"><?php _e('Internal padding of the cookie consent banner.', 'custom-cookie-consent'); ?></p>
                    </div>

                    <div class="form-field">
                        <label for="button_padding"><?php _e('Button Padding', 'custom-cookie-consent'); ?></label>
                        <select id="button_padding" name="button_padding">
                            <option value="5px 10px" <?php selected($design['button_padding'], '5px 10px'); ?>><?php _e('Small (5px 10px)', 'custom-cookie-consent'); ?></option>
                            <option value="8px 16px" <?php selected($design['button_padding'], '8px 16px'); ?>><?php _e('Medium (8px 16px)', 'custom-cookie-consent'); ?></option>
                            <option value="10px 20px" <?php selected($design['button_padding'], '10px 20px'); ?>><?php _e('Large (10px 20px)', 'custom-cookie-consent'); ?></option>
                        </select>
                        <p class="description"><?php _e('Padding inside the buttons.', 'custom-cookie-consent'); ?></p>
                    </div>

                    <div class="form-field">
                        <label for="elements_spacing"><?php _e('Elements Spacing', 'custom-cookie-consent'); ?></label>
                        <select id="elements_spacing" name="elements_spacing">
                            <option value="5px" <?php selected($design['elements_spacing'], '5px'); ?>><?php _e('Compact (5px)', 'custom-cookie-consent'); ?></option>
                            <option value="10px" <?php selected($design['elements_spacing'], '10px'); ?>><?php _e('Standard (10px)', 'custom-cookie-consent'); ?></option>
                            <option value="15px" <?php selected($design['elements_spacing'], '15px'); ?>><?php _e('Comfortable (15px)', 'custom-cookie-consent'); ?></option>
                            <option value="20px" <?php selected($design['elements_spacing'], '20px'); ?>><?php _e('Spacious (20px)', 'custom-cookie-consent'); ?></option>
                        </select>
                        <p class="description"><?php _e('Spacing between elements within the banner.', 'custom-cookie-consent'); ?></p>
                    </div>

                    <div class="form-field">
                        <label for="border_radius"><?php _e('Border Radius', 'custom-cookie-consent'); ?></label>
                        <select id="border_radius" name="border_radius">
                            <option value="0" <?php selected($design['border_radius'], '0'); ?>><?php _e('None (0px)', 'custom-cookie-consent'); ?></option>
                            <option value="4px" <?php selected($design['border_radius'], '4px'); ?>><?php _e('Small (4px)', 'custom-cookie-consent'); ?></option>
                            <option value="8px" <?php selected($design['border_radius'], '8px'); ?>><?php _e('Medium (8px)', 'custom-cookie-consent'); ?></option>
                            <option value="12px" <?php selected($design['border_radius'], '12px'); ?>><?php _e('Large (12px)', 'custom-cookie-consent'); ?></option>
                            <option value="20px" <?php selected($design['border_radius'], '20px'); ?>><?php _e('Extra Large (20px)', 'custom-cookie-consent'); ?></option>
                        </select>
                        <p class="description"><?php _e('Rounded corners for the banner and buttons.', 'custom-cookie-consent'); ?></p>
                    </div>
                </div>

                <!-- Animation Section -->
                <div id="animation-section" class="tab-content">
                    <div class="form-field">
                        <label for="animation_type"><?php _e('Animation Type', 'custom-cookie-consent'); ?></label>
                        <select id="animation_type" name="animation_type">
                            <option value="none" <?php selected($design['animation_type'], 'none'); ?>><?php _e('None', 'custom-cookie-consent'); ?></option>
                            <option value="fade" <?php selected($design['animation_type'], 'fade'); ?>><?php _e('Fade', 'custom-cookie-consent'); ?></option>
                            <option value="slide" <?php selected($design['animation_type'], 'slide'); ?>><?php _e('Slide', 'custom-cookie-consent'); ?></option>
                        </select>
                        <p class="description"><?php _e('Animation effect when the banner appears and disappears.', 'custom-cookie-consent'); ?></p>
                    </div>

                    <div class="form-field">
                        <label for="animation_speed"><?php _e('Animation Speed', 'custom-cookie-consent'); ?></label>
                        <select id="animation_speed" name="animation_speed">
                            <option value="0.2s" <?php selected($design['animation_speed'], '0.2s'); ?>><?php _e('Fast (0.2s)', 'custom-cookie-consent'); ?></option>
                            <option value="0.3s" <?php selected($design['animation_speed'], '0.3s'); ?>><?php _e('Medium (0.3s)', 'custom-cookie-consent'); ?></option>
                            <option value="0.5s" <?php selected($design['animation_speed'], '0.5s'); ?>><?php _e('Slow (0.5s)', 'custom-cookie-consent'); ?></option>
                        </select>
                        <p class="description"><?php _e('Speed of the animation effect.', 'custom-cookie-consent'); ?></p>
                    </div>

                    <div class="animation-accessibility-notice notice notice-info inline">
                        <p><span class="dashicons dashicons-universal-access"></span> <?php _e('Animations will be disabled if the user has enabled the "reduce motion" setting in their browser (prefers-reduced-motion).', 'custom-cookie-consent'); ?></p>
                    </div>
                </div>

                <!-- Advanced Section -->
                <div id="advanced-section" class="tab-content">
                    <div class="form-field">
                        <label for="mobile_breakpoint"><?php _e('Mobile Breakpoint', 'custom-cookie-consent'); ?></label>
                        <select id="mobile_breakpoint" name="mobile_breakpoint">
                            <option value="576px" <?php selected($design['mobile_breakpoint'], '576px'); ?>><?php _e('Small (576px)', 'custom-cookie-consent'); ?></option>
                            <option value="768px" <?php selected($design['mobile_breakpoint'], '768px'); ?>><?php _e('Medium (768px)', 'custom-cookie-consent'); ?></option>
                            <option value="992px" <?php selected($design['mobile_breakpoint'], '992px'); ?>><?php _e('Large (992px)', 'custom-cookie-consent'); ?></option>
                        </select>
                        <p class="description"><?php _e('Screen width at which the banner switches to mobile layout.', 'custom-cookie-consent'); ?></p>
                    </div>

                    <div class="form-field">
                        <label for="z_index"><?php _e('Z-Index', 'custom-cookie-consent'); ?></label>
                        <input type="number" id="z_index" name="z_index" value="<?php echo esc_attr($design['z_index']); ?>" min="1" max="99999" step="1">
                        <p class="description"><?php _e('Controls the stacking order of the banner. Higher values will appear on top of elements with lower values.', 'custom-cookie-consent'); ?></p>
                    </div>
                </div>
            </div>

            <div class="submit-container">
                <input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Styling Settings', 'custom-cookie-consent'); ?>">
                <button type="button" class="button" id="reset-design-defaults"><?php _e('Reset to Defaults', 'custom-cookie-consent'); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Design settings specific styles */
    .color-text-input {
        width: 100px;
        margin-left: 10px;
        height: 30px;
        vertical-align: middle;
    }

    input[type="color"] {
        width: 40px;
        height: 30px;
        padding: 0;
        vertical-align: middle;
    }

    .banner-preview-container {
        margin-top: 25px;
        border-top: 1px solid #eee;
        padding-top: 20px;
    }

    .banner-preview {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 25px;
        margin: 15px 0;
        background: #f9f9f9;
    }

    .banner-preview-inner {
        background: #fff;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .banner-preview-content h4 {
        margin-top: 0;
        margin-bottom: 10px;
    }

    .banner-preview-content p {
        margin-bottom: 15px;
    }

    .banner-preview-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .preview-button {
        padding: 8px 16px;
        border-radius: 4px;
        border: 1px solid transparent;
        cursor: default;
    }

    .preview-accept {
        background: #4C4CFF;
        color: white;
        border-color: #4C4CFF;
    }

    .preview-decline {
        background: #f5f5f5;
        color: #333;
        border-color: #ddd;
    }

    .preview-save {
        background: #e0e0fd;
        color: #333;
        border-color: #4C4CFF;
    }

    .button-accessibility-notice,
    .color-accessibility-notice,
    .typography-accessibility-notice,
    .animation-accessibility-notice {
        margin-top: 20px !important;
    }

    .notice .dashicons-universal-access {
        color: #2271b1;
        margin-right: 5px;
    }

    .submit-container {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }

    /* Reusing the same tab styles as translations page for consistency */
    .cookie-consent-tabs {
        margin-top: 20px;
    }

    .cookie-consent-tab-nav {
        display: flex;
        border-bottom: 1px solid #ccc;
        margin-bottom: 20px;
        gap: 5px;
        flex-wrap: wrap;
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

        // Sync color inputs (hex input and color picker)
        $('.color-text-input').on('input', function() {
            var colorInputId = $(this).data('color-input');
            $('#' + colorInputId).val($(this).val());
            updatePreview();
        });

        $('input[type="color"]').on('input', function() {
            $('input[data-color-input="' + $(this).attr('id') + '"]').val($(this).val());
            updatePreview();
        });

        // Preview updates
        function updatePreview() {
            // Update banner preview
            var previewBox = $('.banner-preview-inner');
            var bgColor = $('#banner_background_color').val();
            var textColor = $('#banner_text_color').val();
            var borderColor = $('#banner_border_color').val();

            previewBox.css({
                'background-color': bgColor,
                'color': textColor,
                'border-color': borderColor
            });

            // Update buttons preview
            $('.preview-accept').css({
                'background-color': $('#accept_button_background').val(),
                'color': $('#accept_button_text_color').val(),
                'border-color': $('#accept_button_border_color').val()
            });

            $('.preview-save').css({
                'background-color': $('#save_button_background').val(),
                'color': $('#save_button_text_color').val(),
                'border-color': $('#save_button_border_color').val()
            });

            $('.preview-decline').css({
                'background-color': $('#decline_button_background').val(),
                'color': $('#decline_button_text_color').val(),
                'border-color': $('#decline_button_border_color').val()
            });

            // Check contrast for accessibility
            checkContrast(
                $('#banner_text_color').val(),
                $('#banner_background_color').val(),
                '#contrast-status'
            );

            checkContrast(
                $('#accept_button_text_color').val(),
                $('#accept_button_background').val(),
                '#accept-button-contrast-status'
            );

            checkContrast(
                $('#save_button_text_color').val(),
                $('#save_button_background').val(),
                '#save-button-contrast-status'
            );

            checkContrast(
                $('#decline_button_text_color').val(),
                $('#decline_button_background').val(),
                '#decline-button-contrast-status'
            );
        }

        // Function to calculate contrast ratio
        function checkContrast(textColor, bgColor, statusElementId) {
            var contrast = calculateContrastRatio(
                hexToRgb(textColor),
                hexToRgb(bgColor)
            );

            var statusText, statusClass;

            if (contrast >= 7) {
                statusText = 'Excellent contrast: ' + contrast.toFixed(2) + ':1 (WCAG AAA)';
                statusClass = 'good-contrast';
            } else if (contrast >= 4.5) {
                statusText = 'Good contrast: ' + contrast.toFixed(2) + ':1 (WCAG AA)';
                statusClass = 'good-contrast';
            } else if (contrast >= 3) {
                statusText = 'Moderate contrast: ' + contrast.toFixed(2) + ':1 (WCAG AA for large text)';
                statusClass = 'moderate-contrast';
            } else {
                statusText = 'Poor contrast: ' + contrast.toFixed(2) + ':1 (Not WCAG compliant)';
                statusClass = 'poor-contrast';
            }

            $(statusElementId).html(statusText)
                .removeClass('good-contrast moderate-contrast poor-contrast')
                .addClass(statusClass);
        }

        function hexToRgb(hex) {
            // Convert hex to RGB
            hex = hex.replace('#', '');
            var r = parseInt(hex.substring(0, 2), 16);
            var g = parseInt(hex.substring(2, 4), 16);
            var b = parseInt(hex.substring(4, 6), 16);
            return [r, g, b];
        }

        function calculateContrastRatio(rgb1, rgb2) {
            // Calculate relative luminance
            var l1 = calculateLuminance(rgb1);
            var l2 = calculateLuminance(rgb2);

            // Calculate contrast ratio
            var ratio = (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
            return ratio;
        }

        function calculateLuminance(rgb) {
            // Convert RGB values to sRGB
            var srgb = rgb.map(function(val) {
                val = val / 255;
                return val <= 0.03928 ? val / 12.92 : Math.pow((val + 0.055) / 1.055, 2.4);
            });

            // Calculate luminance
            return srgb[0] * 0.2126 + srgb[1] * 0.7152 + srgb[2] * 0.0722;
        }

        // Reset to defaults button
        $('#reset-design-defaults').on('click', function(e) {
            e.preventDefault();
            if (confirm('<?php _e('Are you sure you want to reset all design settings to default values?', 'custom-cookie-consent'); ?>')) {
                // Reset all form inputs to their default values
                // This is a simplified approach - you might want to add additional logic to handle all field types
                $('form.js-design-settings-form').get(0).reset();
                updatePreview();
            }
        });

        // Initialize preview on page load
        updatePreview();

        // Form submission via AJAX
        $('.js-design-settings-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var formData = form.serialize();

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'custom_cookie_save_design',
                    nonce: $('#nonce').val(),
                    formData: formData
                },
                beforeSend: function() {
                    form.find('input[type="submit"]').attr('disabled', true).val('<?php _e('Saving...', 'custom-cookie-consent'); ?>');
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        var notice = $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
                            .hide().insertBefore(form).fadeIn();

                        // Auto remove after 3 seconds
                        setTimeout(function() {
                            notice.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 3000);
                    } else {
                        // Show error message
                        $('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>')
                            .hide().insertBefore(form).fadeIn();
                    }
                },
                error: function() {
                    // Show generic error
                    $('<div class="notice notice-error is-dismissible"><p><?php _e('An error occurred while saving settings.', 'custom-cookie-consent'); ?></p></div>')
                        .hide().insertBefore(form).fadeIn();
                },
                complete: function() {
                    form.find('input[type="submit"]').attr('disabled', false).val('<?php _e('Save Styling Settings', 'custom-cookie-consent'); ?>');
                }
            });
        });
    });
</script>