/**
 * Enhanced Cookie Enforcer
 * 
 * This script dynamically enforces cookie consent preferences
 * based on the user's choices and scanned cookies
 */

(function() {
    // Define storage key for consent
    const STORAGE_KEY = 'devora_cookie_consent';

    // Get consent data
    function getConsentData() {
        // Get the consent cookie
        const cookieValue = document.cookie
            .split('; ')
            .find(row => row.startsWith(STORAGE_KEY))
            ?.split('=')[1];

        if (!cookieValue) {
            return null;
        }

        try {
            // Parse consent data
            const decodedValue = decodeURIComponent(cookieValue);
            return JSON.parse(decodedValue);
        } catch (e) {
            return null;
        }
    }

    // Check consent status by category
    function isCategoryAllowed(category) {
        const consentData = getConsentData();
        return consentData && 
               consentData.categories && 
               consentData.categories[category] === true;
    }

    // Remove a cookie by setting its expiration date in the past
    function removeCookie(name, domain = null, path = '/') {
        const expireDate = new Date();
        expireDate.setTime(expireDate.getTime() - 1);
        
        let cookieString = `${name}=; expires=${expireDate.toUTCString()}; path=${path}`;
        
        // Add domain if provided
        if (domain) {
            cookieString += `; domain=${domain}`;
        }
        
        document.cookie = cookieString;
        
        // Try with other common domains
        if (!domain) {
            const hostname = window.location.hostname;
            const domainParts = hostname.split('.');
            
            if (domainParts.length > 1) {
                // Try with root domain
                const rootDomain = `.${domainParts.slice(-2).join('.')}`;
                document.cookie = `${name}=; expires=${expireDate.toUTCString()}; path=${path}; domain=${rootDomain}`;
            }
        }
    }

    // Enhanced enforcer that uses dynamic rules
    function enforceConsent() {
        // Check if we have dynamic rules
        const rules = window.dynamicCookieRules || {
            analytics: ['_ga', '_gid', '_gat', '_ga_'],
            marketing: [],
            functional: []
        };
        
        // Remove all cookies from categories that aren't allowed
        if (!isCategoryAllowed('analytics')) {
            rules.analytics.forEach(cookieName => {
                removeCookie(cookieName);
                // Also try with pattern matching
                document.cookie.split(';').forEach(cookie => {
                    const name = cookie.trim().split('=')[0];
                    if (name.startsWith(cookieName)) {
                        removeCookie(name);
                    }
                });
            });
            
            // Disable GA
            disableGA();
        }
        
        if (!isCategoryAllowed('marketing')) {
            rules.marketing.forEach(cookieName => {
                removeCookie(cookieName);
                
                // Pattern matching for marketing cookies
                document.cookie.split(';').forEach(cookie => {
                    const name = cookie.trim().split('=')[0];
                    if (name.startsWith(cookieName)) {
                        removeCookie(name);
                    }
                });
            });
        }
        
        if (!isCategoryAllowed('functional')) {
            rules.functional.forEach(cookieName => {
                removeCookie(cookieName);
                
                // Pattern matching for functional cookies
                document.cookie.split(';').forEach(cookie => {
                    const name = cookie.trim().split('=')[0];
                    if (name.startsWith(cookieName)) {
                        removeCookie(name);
                    }
                });
            });
        }

        // Handle unknown cookies - scan all cookies to find non-categorized ones
        const allCookies = document.cookie.split(';');
        allCookies.forEach(cookie => {
            const name = cookie.trim().split('=')[0];
            if (!name) return; // Skip empty names
            
            // Check if this cookie is unknown (not in any category)
            let isKnown = false;
            
            // Check if cookie is in any of the rule categories
            ['analytics', 'marketing', 'functional'].forEach(category => {
                rules[category].forEach(knownCookie => {
                    if (name === knownCookie || name.startsWith(knownCookie)) {
                        isKnown = true;
                    }
                });
            });
            
            // Non-categorized cookies are blocked by default unless they look essential
            if (!isKnown && !isCommonEssentialCookie(name)) {
                // Report this unknown cookie for future scanning
                reportUnknownCookie(name);
                
                // Remove this cookie as it's not categorized (applying the precautionary principle)
                removeCookie(name);
            }
        });
    }
    
    // Check if this looks like an essential cookie by common naming patterns
    function isCommonEssentialCookie(name) {
        const essentialPatterns = [
            /^wp-/i, 
            /^wordpress/i, 
            /^wc_/i,  // WooCommerce
            /^session/i,
            /^csrf/i,
            /^token/i,
            /^PHPSESSID$/i
        ];
        
        return essentialPatterns.some(pattern => pattern.test(name));
    }
    
    // Report an unknown cookie for admin to categorize
    function reportUnknownCookie(name) {
        // Only report if admin ajax exists
        if (typeof ajaxurl !== 'undefined') {
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=report_unknown_cookie&cookie=' + encodeURIComponent(name),
                credentials: 'same-origin'
            }).catch(() => {
                // Silent fail - reporting isn't critical
            });
        } else if (typeof wp !== 'undefined' && wp.ajax && wp.ajax.settings && wp.ajax.settings.url) {
            // Try WordPress AJAX URL if available
            fetch(wp.ajax.settings.url, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=report_unknown_cookie&cookie=' + encodeURIComponent(name),
                credentials: 'same-origin'
            }).catch(() => {
                // Silent fail - reporting isn't critical
            });
        }
    }
    
    // Disable GA if loaded without consent
    function disableGA() {
        // Override GA/gtag if already defined
        if (typeof window.ga === 'function') {
            window.ga = function() {
                return false;
            };
        }
        
        // Override gtag if already defined
        if (typeof window.gtag === 'function') {
            window.gtag = function() {
                return false;
            };
        }
        
        // Prevent future loading
        Object.defineProperty(window, 'ga', {
            configurable: true,
            get: function() { return function() { return false; }; },
            set: function() { }
        });
        
        Object.defineProperty(window, 'gtag', {
            configurable: true,
            get: function() { return function() { return false; }; },
            set: function() { }
        });
    }
    
    // Run initial consent enforcement
    enforceConsent();
    
    // Monitor for cookie changes (new cookies being set)
    setInterval(() => {
        enforceConsent();
    }, 2000); // Check every 2 seconds
    
    // Add event listener for consent update
    window.addEventListener('consentUpdated', function(e) {
        // Re-enforce the consent when the preferences change
        setTimeout(enforceConsent, 100);
    });
})(); 