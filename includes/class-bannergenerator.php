<?php

/**
 * Banner Generator Class
 *
 * Dynamically generates the cookie consent banner based on detected cookies.
 *
 * @package CustomCookieConsent
 */

namespace CustomCookieConsent;

/**
 * Banner Generator Class
 *
 * This class is responsible for generating the cookie consent banner,
 * based on the detected cookies and user settings.
 *
 * @since 1.0.0
 */
class BannerGenerator
{
















    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('custom_cookie_rules_updated', array($this, 'update_banner_template'));
        add_filter('custom_cookie_consent_banner_template', array($this, 'filter_banner_template'));
    }

    /**
     * Updates the banner template when cookie rules are updated.
     *
     * @return void
     */
    public function update_banner_template()
    {
        // Get all categorized cookies.
        $detected   = get_option('custom_cookie_detected', array());
        $categories = array();

        // Group cookies by category.
        foreach ($detected as $cookie) {
            if ('categorized' === $cookie['status']) {
                $category = $cookie['category'];
                if (! isset($categories[$category])) {
                    $categories[$category] = array(
                        'cookies' => array(),
                        'sources' => array(),
                    );
                }

                $categories[$category]['cookies'][] = $cookie;

                if (! empty($cookie['source']) && ! in_array($cookie['source'], $categories[$category]['sources'], true)) {
                    $categories[$category]['sources'][] = $cookie['source'];
                }
            }
        }

        // Get banner settings - force fresh settings (not cached).
        $settings = get_option('custom_cookie_settings', array());

        // Ensure we have the latest translation changes.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (function_exists('wp_add_inline_script')) {
                $this->log_debug('Banner Generator: Regenerating template with settings.');
            }
        }

        // Generate banner template.
        $template = $this->generate_template($categories, $settings);

        // Add timestamp to force browser refresh.
        $template = str_replace(
            'window.bannerTemplate = bannerTemplate;',
            'window.bannerTemplate = bannerTemplate; window.templateTimestamp = "' . time() . '";',
            $template
        );

        // Save the template.
        update_option('custom_cookie_banner_template', $template);

        // Also update a timestamp option to track when the template was last updated.
        update_option('custom_cookie_banner_last_updated', time());
    }

    /**
     * Generates the banner template JavaScript based on settings and categories.
     *
     * @param array $categories Array of cookie categories.
     * @param array $settings Plugin settings.
     * @return string Generated JavaScript template.
     */
    public function generate_template($categories, $settings)
    {
        // Start with the banner shell.
        ob_start();

        // Get position - check both settings keys for backwards compatibility.
        $position = 'bottom'; // Default to bottom.
        if (isset($settings['position']) && in_array($settings['position'], array('bottom', 'top', 'center'), true)) {
            $position = $settings['position'];
        } elseif (isset($settings['banner_position']) && in_array($settings['banner_position'], array('bottom', 'top', 'center'), true)) {
            $position = $settings['banner_position'];
        }
?>
        const bannerTemplate = `
        <div class="cookie-consent-banner" role="dialog" aria-modal="true" aria-labelledby="cookie-consent-title" aria-describedby="cookie-consent-description" data-timestamp="<?php echo esc_js($settings['last_updated'] ?? time()); ?>" data-position="<?php echo esc_js($position); ?>">
            <button class="cookie-consent-close" aria-label="<?php echo esc_js($settings['close_button_aria_label'] ?? 'Lukk cookie-banner'); ?>"><?php echo esc_js($settings['close_button_text'] ?? 'Lukk'); ?> <span class="close-x" aria-hidden="true">×</span></button>
            <div class="cookie-consent-content">
                <header>
                    <h3 id="cookie-consent-title"><?php echo esc_js($settings['banner_title'] ?? 'Vi bruker informasjonskapsler (cookies)'); ?></h3>
                    <p id="cookie-consent-description"><?php echo esc_js($settings['banner_text'] ?? 'Vi bruker informasjonskapsler for å forbedre brukeropplevelsen, tilby personlig tilpasset innhold og analysere trafikken vår.'); ?></p>
                </header>

                <section class="cookie-consent-options" aria-labelledby="cookie-consent-title">
                    <!-- Necessary cookies - always included. -->
                    <div class="cookie-category">
                        <label class="toggle-switch" for="necessary-cookie-toggle">
                            <input type="checkbox" id="necessary-cookie-toggle" checked disabled data-category="necessary">
                            <span class="slider" aria-hidden="true"></span>
                        </label>
                        <div class="category-info">
                            <h4 id="necessary-category-heading"><?php echo esc_js($settings['necessary_title'] ?? 'Nødvendige'); ?></h4>
                            <p id="necessary-category-description"><?php echo esc_js($settings['necessary_description'] ?? 'Disse informasjonskapslene er nødvendige for at nettstedet skal fungere.'); ?></p>
                            <?php if (! empty($categories['necessary']['sources'])) : ?>
                                <small><?php echo esc_js($settings['sources_label'] ?? 'Brukes av:'); ?> <?php echo esc_js(implode(', ', $categories['necessary']['sources'])); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Analytics cookies. -->
                    <?php if (isset($settings['analytics_title']) && ! empty($settings['analytics_title'])) : ?>
                        <div class="cookie-category">
                            <label class="toggle-switch" for="analytics-cookie-toggle">
                                <input type="checkbox" id="analytics-cookie-toggle" data-category="analytics">
                                <span class="slider" aria-hidden="true"></span>
                            </label>
                            <div class="category-info">
                                <h4 id="analytics-category-heading"><?php echo esc_js($settings['analytics_title'] ?? 'Analyse'); ?></h4>
                                <p id="analytics-category-description"><?php echo esc_js($settings['analytics_description'] ?? 'Disse informasjonskapslene hjelper oss å forstå hvordan besøkende bruker nettstedet.'); ?></p>
                                <?php if (! empty($categories['analytics']['sources'])) : ?>
                                    <small><?php echo esc_js($settings['sources_label'] ?? 'Brukes av:'); ?> <?php echo esc_js(implode(', ', $categories['analytics']['sources'])); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Functional cookies. -->
                    <?php if (isset($settings['functional_title']) && ! empty($settings['functional_title'])) : ?>
                        <div class="cookie-category">
                            <label class="toggle-switch" for="functional-cookie-toggle">
                                <input type="checkbox" id="functional-cookie-toggle" data-category="functional">
                                <span class="slider" aria-hidden="true"></span>
                            </label>
                            <div class="category-info">
                                <h4 id="functional-category-heading"><?php echo esc_js($settings['functional_title'] ?? 'Funksjonell'); ?></h4>
                                <p id="functional-category-description"><?php echo esc_js($settings['functional_description'] ?? 'Disse informasjonskapslene gjør at nettstedet kan gi forbedret funksjonalitet.'); ?></p>
                                <?php if (! empty($categories['functional']['sources'])) : ?>
                                    <small><?php echo esc_js($settings['sources_label'] ?? 'Brukes av:'); ?> <?php echo esc_js(implode(', ', $categories['functional']['sources'])); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Marketing cookies. -->
                    <?php if (isset($settings['marketing_title']) && ! empty($settings['marketing_title'])) : ?>
                        <div class="cookie-category">
                            <label class="toggle-switch" for="marketing-cookie-toggle">
                                <input type="checkbox" id="marketing-cookie-toggle" data-category="marketing">
                                <span class="slider" aria-hidden="true"></span>
                            </label>
                            <div class="category-info">
                                <h4 id="marketing-category-heading"><?php echo esc_js($settings['marketing_title'] ?? 'Markedsføring'); ?></h4>
                                <p id="marketing-category-description"><?php echo esc_js($settings['marketing_description'] ?? 'Disse informasjonskapslene brukes til å vise målrettet markedsføring.'); ?></p>
                                <?php if (! empty($categories['marketing']['sources'])) : ?>
                                    <small><?php echo esc_js($settings['sources_label'] ?? 'Brukes av:'); ?> <?php echo esc_js(implode(', ', $categories['marketing']['sources'])); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>

                <div class="cookie-consent-buttons" role="group" aria-label="Samtykkevalg for informasjonskapsler">
                    <button type="button" class="cookie-consent-decline"><?php echo esc_js($settings['decline_button'] ?? 'Avslå alle'); ?></button>
                    <button type="button" class="cookie-consent-save-custom"><?php echo esc_js($settings['save_button'] ?? 'Lagre preferanser'); ?></button>
                    <button type="button" class="cookie-consent-accept"><?php echo esc_js($settings['accept_button'] ?? 'Godta alle'); ?></button>
                </div>

                <footer class="cookie-consent-footer">
                    <p class="cookie-consent-links">
                        <?php if (! empty($settings['privacy_url'])) : ?>
                            <a href="<?php echo esc_js($settings['privacy_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_js($settings['privacy_text'] ?? 'Personvernerklæring'); ?></a>
                        <?php endif; ?>

                        <?php if (! empty($settings['privacy_url']) && ! empty($settings['cookie_policy_url'])) : ?>
                            |
                        <?php endif; ?>

                        <?php if (! empty($settings['cookie_policy_url'])) : ?>
                            <a href="<?php echo esc_js($settings['cookie_policy_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_js($settings['cookie_policy_text'] ?? 'Cookie Policy'); ?></a>
                        <?php endif; ?>
                    </p>
                    <div class="cookie-consent-branding">
                        <a href="https://devora.no" target="_blank" rel="noopener noreferrer" class="devora-branding">
                            Powered by <span class="devora-name">Devora</span>
                        </a>
                    </div>
                </footer>
            </div>
        </div>`;
<?php
        $template = ob_get_clean();
        return $template;
    }

    /**
     * Filters the banner template with the one from the database.
     *
     * @param string $template The original template.
     * @return string The filtered template.
     */
    public function filter_banner_template($template)
    {
        // Always get the latest generated template from the database.
        $generated = get_option('custom_cookie_banner_template');

        // If debugging is enabled, add some debug information.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($generated) {
                // Using WordPress-compatible debug logging.
                $this->log_debug('Banner Generator: Using dynamically generated template.');
            } else {
                // Using WordPress-compatible debug logging.
                $this->log_debug('Banner Generator: No generated template found, using default.');
            }
        }

        return $generated ? $generated : $template;
    }

    /**
     * Log debug information when WP_DEBUG is enabled.
     *
     * @param string $message The debug message.
     * @param mixed  $data Optional data to include in the log.
     * @return void
     */
    protected function log_debug($message, $data = null)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (null === $data) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log($message);
            } else {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
                error_log($message . ' ' . print_r($data, true));
            }
        }
    }

    /**
     * Generate the cookie consent banner HTML.
     *
     * @return string The HTML for the cookie consent banner.
     */
    public function generate_banner(): string
    {
        $settings   = get_option('custom_cookie_settings', array());
        $categories = (new CookieCategories())->get_categories();

        // Add meta tags for SEO.
        add_action(
            'wp_head',
            function () use ($settings) {
                $banner_title = $settings['banner_title'] ?? __('Cookie Consent', 'custom-cookie-consent');
                $banner_text  = $settings['banner_text'] ?? __('We use cookies to improve your experience on our website.', 'custom-cookie-consent');

                echo '<meta name="description" content="' . esc_attr($banner_title . ' - ' . $banner_text) . '" />';
                echo '<meta name="keywords" content="cookie consent, GDPR compliance, cookie policy, privacy, data protection" />';
            },
            5
        );

        // Banner position - check both 'position' and 'banner_position' for backwards compatibility.
        $position = 'bottom'; // Default to bottom.
        if (isset($settings['position']) && in_array($settings['position'], array('bottom', 'top', 'center'), true)) {
            $position = $settings['position'];
        } elseif (isset($settings['banner_position']) && in_array($settings['banner_position'], array('bottom', 'top', 'center'), true)) {
            $position = $settings['banner_position'];
        }
        $position_class = 'position-' . $position;

        // Start building the banner HTML.
        $output = '<div id="cookie-consent-banner" class="cookie-consent-banner ' . $position_class . '" role="dialog" aria-modal="true" aria-labelledby="cookie-consent-title" data-position="' . esc_attr($position) . '">';

        // Banner content.
        $output .= '<div class="cookie-consent-container">';

        // Header section with title and text.
        $output .= '<header class="cookie-consent-header">';
        $output .= '<h2 id="cookie-consent-title">' . esc_html($settings['banner_title'] ?? __('Cookie Consent', 'custom-cookie-consent')) . '</h2>';
        $output .= '<p>' . esc_html($settings['banner_text'] ?? __('We use cookies to improve your experience on our website.', 'custom-cookie-consent')) . '</p>';
        $output .= '</header>';

        // Cookie categories section.
        $output .= '<section class="cookie-consent-options" aria-labelledby="cookie-consent-title">';

        foreach ($categories as $category) {
            $category_id = $category['id'];
            $required    = 'necessary' === $category_id;
            $checked     = $required ? 'checked disabled' : '';

            $output .= '<div class="cookie-category">';
            $output .= '<div class="cookie-category-header">';
            $output .= '<input type="checkbox" id="cookie-category-' . $category_id . '" name="cookie-category[]" value="' . $category_id . '" ' . $checked . '>';
            $output .= '<label for="cookie-category-' . $category_id . '">' . esc_html($category['title']) . ($required ? ' (' . __('Required', 'custom-cookie-consent') . ')' : '') . '</label>';
            $output .= '</div>';
            $output .= '<div class="cookie-category-description">';
            $output .= '<p>' . esc_html($category['description']) . '</p>';
            $output .= '</div>';
            $output .= '</div>';
        }

        $output .= '</section>';

        // Buttons section.
        $output .= '<div class="cookie-consent-actions" role="group" aria-label="' . esc_attr__('Cookie Consent Options', 'custom-cookie-consent') . '">';

        // Accept all button.
        $output .= '<button type="button" id="cookie-consent-accept-all" class="cookie-consent-button cookie-consent-accept-all">';
        $output .= esc_html($settings['accept_button_text'] ?? __('Accept All', 'custom-cookie-consent'));
        $output .= '</button>';

        // Decline button.
        $output .= '<button type="button" id="cookie-consent-decline" class="cookie-consent-button cookie-consent-decline">';
        $output .= esc_html($settings['decline_button_text'] ?? __('Decline', 'custom-cookie-consent'));
        $output .= '</button>';

        // Save preferences button.
        $output .= '<button type="button" id="cookie-consent-save" class="cookie-consent-button cookie-consent-save">';
        $output .= esc_html($settings['save_button_text'] ?? __('Save Preferences', 'custom-cookie-consent'));
        $output .= '</button>';

        $output .= '</div>';

        // Footer with links.
        $output .= '<footer class="cookie-consent-footer">';

        // Privacy policy link.
        if (! empty($settings['privacy_url'])) {
            $output .= '<a href="' . esc_url($settings['privacy_url']) . '" target="_blank" rel="noopener noreferrer">';
            $output .= esc_html($settings['privacy_link_text'] ?? __('Privacy Policy', 'custom-cookie-consent'));
            $output .= '</a>';
        }

        // Cookie policy link.
        if (! empty($settings['cookie_policy_url'])) {
            if (! empty($settings['privacy_url'])) {
                $output .= ' | ';
            }
            $output .= '<a href="' . esc_url($settings['cookie_policy_url']) . '" target="_blank" rel="noopener noreferrer">';
            $output .= esc_html($settings['cookie_policy_link_text'] ?? __('Cookie Policy', 'custom-cookie-consent'));
            $output .= '</a>';
        }

        // Cookie settings link.
        $output .= ' | <a href="#" id="cookie-settings-link">';
        $output .= esc_html($settings['settings_link_text'] ?? __('Cookie Settings', 'custom-cookie-consent'));
        $output .= '</a>';

        // Add Devora branding.
        $output .= '<div class="cookie-consent-branding">';
        $output .= '<a href="https://devora.no" target="_blank" rel="noopener noreferrer" class="devora-branding">';
        $output .= __('Powered by', 'custom-cookie-consent') . ' <span class="devora-name">Devora</span>';
        $output .= '</a>';
        $output .= '</div>';

        $output .= '</footer>';

        $output .= '</div>'; // End of container.
        $output .= '</div>'; // End of banner.

        return $output;
    }

    /**
     * Force regeneration of the banner template.
     *
     * Used when position setting changes to ensure banner displays correctly.
     *
     * @return void
     */
    public function force_template_regeneration()
    {
        // Static variable to prevent infinite loops
        static $regenerating = false;

        if ($regenerating) {
            return;
        }

        $regenerating = true;

        // Get all categorized cookies
        $detected   = get_option('custom_cookie_detected', array());
        $categories = array();

        // Group cookies by category
        foreach ($detected as $cookie) {
            if ('categorized' === $cookie['status']) {
                $category = $cookie['category'];
                if (! isset($categories[$category])) {
                    $categories[$category] = array(
                        'cookies' => array(),
                        'sources' => array(),
                    );
                }

                $categories[$category]['cookies'][] = $cookie;

                if (! empty($cookie['source']) && ! in_array($cookie['source'], $categories[$category]['sources'], true)) {
                    $categories[$category]['sources'][] = $cookie['source'];
                }
            }
        }

        // Get banner settings
        $settings = get_option('custom_cookie_settings', array());

        // Generate banner template
        $template = $this->generate_template($categories, $settings);

        // Add timestamp to force browser refresh
        $template = str_replace(
            'window.bannerTemplate = bannerTemplate;',
            'window.bannerTemplate = bannerTemplate; window.templateTimestamp = "' . time() . '";',
            $template
        );

        // Save the template
        update_option('custom_cookie_banner_template', $template);

        // Update timestamp
        update_option('custom_cookie_banner_last_updated', time());

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log_debug('Banner template forcefully regenerated due to position change');
        }

        $regenerating = false;
    }
}
