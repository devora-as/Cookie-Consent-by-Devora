<?php

namespace CustomCookieConsent;

class CookieCategories
{
    public static function get_categories()
    {
        // Get settings for titles and descriptions
        $settings = get_option('custom_cookie_settings', []);

        return [
            'necessary' => [
                'required' => true,
                'title' => $settings['necessary_title'] ?? 'Nødvendige',
                'description' => $settings['necessary_description'] ?? 'Disse informasjonskapslene er nødvendige for at nettstedet skal fungere og kan ikke deaktiveres.',
                'cookies' => [
                    ['name' => '__hssc', 'domain' => '.devora.no'],
                    ['name' => '__hssrc', 'domain' => '.devora.no'],
                    ['name' => '__cf_bm', 'domain' => [
                        '.hubspot.com',
                        '.hs-analytics.net',
                        '.hs-banner.com',
                        '.hubapi.com',
                        '.hs-scripts.com',
                        '.hsadspixel.net',
                        '.hscollectedforms.net'
                    ]],
                    ['name' => '_cfuvid', 'domain' => '.hubspot.com']
                ]
            ],
            'analytics' => [
                'required' => false,
                'title' => $settings['analytics_title'] ?? 'Analyse',
                'description' => $settings['analytics_description'] ?? 'Disse informasjonskapslene hjelper oss å forstå hvordan besøkende bruker nettstedet.',
                'cookies' => [
                    ['name' => 'hubspotutk', 'domain' => '.devora.no'],
                    ['name' => '__hstc', 'domain' => '.devora.no']
                ]
            ],
            'functional' => [
                'required' => false,
                'title' => $settings['functional_title'] ?? 'Funksjonell',
                'description' => $settings['functional_description'] ?? 'Disse informasjonskapslene gjør at nettstedet kan gi forbedret funksjonalitet og personlig tilpasning.',
                'cookies' => [
                    ['name' => '_lscache_vary', 'domain' => '.devora.no']
                ]
            ],
            'marketing' => [
                'required' => false,
                'title' => $settings['marketing_title'] ?? 'Markedsføring',
                'description' => $settings['marketing_description'] ?? 'Disse informasjonskapslene brukes til å spore besøkende på tvers av nettsteder for å vise relevante annonser.',
                'cookies' => []
            ]
        ];
    }
}
