/**
 * Enhanced Cookie Enforcer
 *
 * This script dynamically enforces cookie consent preferences
 * based on the user's choices and scanned cookies
 */

(function () {
  // Define storage key for consent
  const STORAGE_KEY = "devora_cookie_consent";

  // Get consent data
  function getConsentData() {
    // Get the consent cookie
    const cookieValue = document.cookie
      .split("; ")
      .find((row) => row.startsWith(STORAGE_KEY))
      ?.split("=")[1];

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
    return (
      consentData &&
      consentData.categories &&
      consentData.categories[category] === true
    );
  }

  // Remove a cookie by setting its expiration date in the past
  function removeCookie(name, domain = null, path = "/") {
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
      const domainParts = hostname.split(".");

      if (domainParts.length > 1) {
        // Try with root domain
        const rootDomain = `.${domainParts.slice(-2).join(".")}`;
        document.cookie = `${name}=; expires=${expireDate.toUTCString()}; path=${path}; domain=${rootDomain}`;
      }
    }
  }

  // Enhanced enforcer that uses dynamic rules
  function enforceConsent() {
    const consent = getConsentData();

    if (!consent || !consent.categories) {
      if (window.cookieConsentSettings?.debug) {
        console.log("No consent data found, blocking all tracking");
      }
      disableGA();
      return;
    }

    // If analytics is not consented, disable tracking
    if (!consent.categories.analytics) {
      if (window.cookieConsentSettings?.debug) {
        console.log("Analytics not consented, disabling tracking");
      }
      disableGA();
    } else {
      // Restore original tracking if it was disabled
      if (window._originalDataLayer) {
        window.dataLayer = window._originalDataLayer;
        delete window._originalDataLayer;
      }
      if (window._originalGtag) {
        window.gtag = window._originalGtag;
        delete window._originalGtag;
      }
    }

    // Define known cookies for each category
    const knownCookies = {
      analytics: ["_ga", "_gid", "_gat", "_ga_", "_fbp", "_fbc", "_gcl_au"],
      marketing: ["_fbp", "_fbc", "_gcl_au", "IDE", "test_cookie", "fr"],
      functional: [
        "wordpress_test_cookie",
        "wp-settings-time-",
        "wp-settings-",
      ],
    };

    // Remove cookies for non-consented categories
    Object.entries(knownCookies).forEach(([category, cookies]) => {
      if (!isCategoryAllowed(category)) {
        cookies.forEach((cookieName) => {
          removeCookie(cookieName);
          // Also try with pattern matching
          document.cookie.split(";").forEach((cookie) => {
            const name = cookie.trim().split("=")[0];
            if (name.startsWith(cookieName)) {
              removeCookie(name);
            }
          });
        });
      }
    });

    // Handle unknown cookies - scan all cookies to find non-categorized ones
    const allCookies = document.cookie.split(";");
    allCookies.forEach((cookie) => {
      const name = cookie.trim().split("=")[0];
      if (!name) return; // Skip empty names

      // Check if this cookie is unknown (not in any category)
      let isKnown = false;

      // Check if cookie is in any of the known cookie lists
      Object.values(knownCookies).forEach((cookieList) => {
        cookieList.forEach((knownCookie) => {
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
      /^wc_/i, // WooCommerce
      /^session/i,
      /^csrf/i,
      /^token/i,
      /^PHPSESSID$/i,
    ];

    return essentialPatterns.some((pattern) => pattern.test(name));
  }

  // Report an unknown cookie for admin to categorize
  function reportUnknownCookie(name) {
    // Only report if admin ajax exists
    if (typeof ajaxurl !== "undefined") {
      fetch(ajaxurl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=report_unknown_cookie&cookie=" + encodeURIComponent(name),
        credentials: "same-origin",
      }).catch(() => {
        // Silent fail - reporting isn't critical
      });
    } else if (
      typeof wp !== "undefined" &&
      wp.ajax &&
      wp.ajax.settings &&
      wp.ajax.settings.url
    ) {
      // Try WordPress AJAX URL if available
      fetch(wp.ajax.settings.url, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=report_unknown_cookie&cookie=" + encodeURIComponent(name),
        credentials: "same-origin",
      }).catch(() => {
        // Silent fail - reporting isn't critical
      });
    }
  }

  // Disable GA if loaded without consent
  function disableGA() {
    // Instead of redefining gtag, we'll override window.dataLayer
    if (typeof window.dataLayer !== "undefined") {
      // Store the original dataLayer if it exists
      if (!window._originalDataLayer) {
        window._originalDataLayer = window.dataLayer;
      }

      // Create a new dataLayer that blocks tracking
      window.dataLayer = {
        push: function (args) {
          // Allow consent mode updates to pass through
          if (args && (args[0] === "consent" || args[0] === "set")) {
            if (window._originalDataLayer && window._originalDataLayer.push) {
              window._originalDataLayer.push(args);
            }
          }
          // Block other tracking calls
          if (window.cookieConsentSettings?.debug) {
            console.log("Blocked GA event:", args);
          }
        },
      };
    }

    // Handle existing gtag function - use a safer approach with proper error handling
    if (typeof window.gtag === "function" && !window._originalGtag) {
      try {
        // Save original gtag
        window._originalGtag = window.gtag;

        // Create a safer proxy using a wrapper function
        const gtagProxy = function () {
          // Allow consent-related calls
          if (arguments[0] === "consent") {
            if (window._originalGtag) {
              window._originalGtag.apply(null, arguments);
            }
          }
          // Block other tracking calls
          if (window.cookieConsentSettings?.debug) {
            console.log("Blocked gtag call:", arguments);
          }
        };

        // Try wrapping gtag using Object.defineProperty to avoid the "redefine property" error
        try {
          const descriptor = Object.getOwnPropertyDescriptor(window, "gtag");
          if (descriptor && !descriptor.configurable) {
            // Property is not configurable, use a different approach
            // This approach intercepts calls by manipulating the dataLayer instead
            if (window.cookieConsentSettings?.debug) {
              console.log(
                "gtag is not configurable, using dataLayer interception instead"
              );
            }
          } else {
            Object.defineProperty(window, "gtag", {
              configurable: true,
              get: function () {
                return gtagProxy;
              },
            });
          }
        } catch (propErr) {
          if (window.cookieConsentSettings?.debug) {
            console.log("Error overriding gtag:", propErr);
          }
        }
      } catch (e) {
        if (window.cookieConsentSettings?.debug) {
          console.log("Error handling gtag:", e);
        }
      }
    }
  }

  // Run initial consent enforcement
  enforceConsent();

  // Monitor for cookie changes (new cookies being set)
  setInterval(() => {
    enforceConsent();
  }, 2000); // Check every 2 seconds

  // Add event listener for consent update
  window.addEventListener("consentUpdated", function (e) {
    // Re-enforce the consent when the preferences change
    setTimeout(enforceConsent, 100);
  });
})();
