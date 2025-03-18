<?php

/**
 * Class TestCookieConsent
 *
 * @package Custom_Cookie_Consent
 */

use CustomCookieConsent\CookieConsent;

class TestCookieConsent extends WP_UnitTestCase
{

    /**
     * Test instance creation
     */
    public function test_get_instance()
    {
        $instance = CookieConsent::get_instance();
        $this->assertInstanceOf(CookieConsent::class, $instance);

        // Test singleton pattern
        $second_instance = CookieConsent::get_instance();
        $this->assertSame($instance, $second_instance);
    }

    /**
     * Test cookie settings link generation
     */
    public function test_get_cookie_settings_link()
    {
        $link = CookieConsent::get_cookie_settings_link('test-class', 'Test Text');
        $this->assertStringContainsString('test-class', $link);
        $this->assertStringContainsString('Test Text', $link);
        $this->assertStringContainsString('href="#"', $link);
        $this->assertStringContainsString('data-cc-settings-btn', $link);
    }
}
