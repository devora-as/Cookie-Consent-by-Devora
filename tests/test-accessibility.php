<?php

/**
 * Class TestAccessibility
 *
 * Tests accessibility compliance for the cookie consent banner
 *
 * @package Custom_Cookie_Consent
 */

use CustomCookieConsent\BannerGenerator;

class TestAccessibility extends WP_UnitTestCase
{

    /**
     * Test if the cookie banner has proper aria attributes
     */
    public function test_banner_aria_attributes()
    {
        $banner_generator = new BannerGenerator();
        $banner_html = $banner_generator->generate_banner_html();

        // Test modal dialog has proper role
        $this->assertStringContainsString('role="dialog"', $banner_html);

        // Test aria-labelledby is present
        $this->assertStringContainsString('aria-labelledby', $banner_html);

        // Test aria-describedby is present
        $this->assertStringContainsString('aria-describedby', $banner_html);
    }

    /**
     * Test if the banner buttons are properly labeled
     */
    public function test_button_accessibility()
    {
        $banner_generator = new BannerGenerator();
        $banner_html = $banner_generator->generate_banner_html();

        // Test buttons have accessible text
        $this->assertStringContainsString('<button', $banner_html);

        // Test buttons have proper focus styling
        $css = file_get_contents(plugin_dir_path(dirname(__FILE__)) . 'css/cookie-consent.css');
        $this->assertStringContainsString(':focus', $css);
    }

    /**
     * Test keyboard navigation 
     */
    public function test_keyboard_navigation()
    {
        $banner_generator = new BannerGenerator();
        $banner_html = $banner_generator->generate_banner_html();

        // Test tab index for keyboard navigation
        $this->assertStringNotContainsString('tabindex="1"', $banner_html);
        $this->assertStringNotContainsString('tabindex="2"', $banner_html);
        $this->assertStringNotContainsString('tabindex="3"', $banner_html);

        // Should only have tabindex 0 or -1
        if (strpos($banner_html, 'tabindex') !== false) {
            $this->assertMatchesRegularExpression('/tabindex=["\'](0|-1)["\']/', $banner_html);
        }
    }

    /**
     * Test color contrast
     */
    public function test_color_contrast()
    {
        // This is a placeholder for automated color contrast testing
        // In a real-world scenario, we would use a tool like Pa11y 
        // to perform this test as it requires rendering the page
        $this->markTestSkipped('Color contrast testing requires Pa11y or similar tool');
    }
}
