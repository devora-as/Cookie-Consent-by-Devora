/**
 * Cookie Enforcer - Handles cookie removal based on user consent choices
 */
(function () {
  "use strict";

  // Wait until DOM is ready
  function init() {
    // Handle consent manager ready event
    document.addEventListener("consentManagerReady", function () {
      if (window.cookieConsentSettings?.debug) {
        console.log("Cookie enforcer: initializing");
      }

      // Define known cookies for each category
      const knownCookies = {
        necessary: [
          "devora_cookie_consent",
          "wordpress_test_cookie",
          "wp-settings-time",
          "wp_lang",
          "wp-settings",
          "wordpress_logged_in",
        ],
        analytics: [
          "_ga",
          "_gid",
          "_gat",
          "_ga_", // Google Analytics
          "_pk_id",
          "_pk_ses",
          "_pk_cvar",
          "_pk_hsr", // Matomo
          "mtm_consent",
          "mtm_consent_removed", // Matomo Tag Manager
          "matomo_ignore",
          "MATOMO_SESSID", // Matomo misc
        ],
        functional: [
          "wp_woocommerce_session",
          "woocommerce_cart_hash",
          "woocommerce_items_in_cart",
          "wordpress_sec",
        ],
        marketing: [
          "NID",
          "_fbp",
          "fr",
          "tr", // Facebook
          "IDE",
          "MUID",
          "MUIDB", // DoubleClick/Google
          "personalization_id",
          "guest_id", // Twitter
        ],
      };

      // Function to safely get consent data
      function getConsentData() {
        try {
          // Check if the consent manager global is available
          if (
            typeof window.devoraCookieConsent !== "undefined" &&
            typeof window.devoraCookieConsent.getConsentState === "function"
          ) {
            return (
              window.devoraCookieConsent.getConsentState() || {
                necessary: true,
                analytics: false,
                functional: false,
                marketing: false,
              }
            );
          }

          // Fallback: check cookie directly
          const cookieValue = document.cookie
            .split("; ")
            .find((row) => row.startsWith("devora_cookie_consent="));

          if (cookieValue) {
            try {
              const consentData = JSON.parse(
                decodeURIComponent(cookieValue.split("=")[1])
              );
              if (consentData && consentData.categories) {
                return {
                  necessary: true, // Always true
                  analytics: !!consentData.categories.analytics,
                  functional: !!consentData.categories.functional,
                  marketing: !!consentData.categories.marketing,
                };
              }
            } catch (parseError) {
              console.error(
                "Cookie enforcer: Error parsing consent data",
                parseError
              );
            }
          }

          // Default to only necessary if error or no data
          return {
            necessary: true,
            analytics: false,
            functional: false,
            marketing: false,
          };
        } catch (error) {
          console.error("Cookie enforcer: Error getting consent data", error);
          return {
            necessary: true,
            analytics: false,
            functional: false,
            marketing: false,
          };
        }
      }

      // Function to check if a cookie belongs to a known category
      function getCookieCategory(cookieName) {
        if (!cookieName) return null;

        for (const category in knownCookies) {
          for (const pattern of knownCookies[category]) {
            if (cookieName.startsWith(pattern) || cookieName === pattern) {
              return category;
            }
          }
        }
        return null; // Unknown category
      }

      // Function to safely remove a cookie
      function removeCookie(name) {
        if (!name) return;

        try {
          document.cookie =
            name +
            "=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Domain=" +
            window.location.hostname +
            ";";
          document.cookie =
            name + "=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;";

          // Also try with www subdomain removed
          const domainWithoutWww = window.location.hostname.replace(
            /^www\./,
            ""
          );
          if (domainWithoutWww !== window.location.hostname) {
            document.cookie =
              name +
              "=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Domain=" +
              domainWithoutWww +
              ";";
          }

          if (window.cookieConsentSettings?.debug) {
            console.log("Cookie enforcer: Removed cookie", name);
          }
        } catch (error) {
          console.error("Cookie enforcer: Error removing cookie", name, error);
        }
      }

      // Process cookies based on consent
      function processCookies() {
        try {
          const consentData = getConsentData();

          if (window.cookieConsentSettings?.debug) {
            console.log(
              "Cookie enforcer: Processing cookies with consent",
              consentData
            );
          }

          // Get all cookies
          const cookies = {};
          document.cookie.split(";").forEach((cookie) => {
            const parts = cookie.trim().split("=");
            const name = parts.shift().trim();
            if (name) {
              cookies[name] = parts.join("=");
            }
          });

          // 1. Remove cookies for non-consented categories
          for (const category in knownCookies) {
            if (category !== "necessary" && !consentData[category]) {
              for (const pattern of knownCookies[category]) {
                for (const cookieName in cookies) {
                  if (
                    cookieName.startsWith(pattern) ||
                    cookieName === pattern
                  ) {
                    removeCookie(cookieName);
                  }
                }
              }
            }
          }

          // 2. Handle unknown cookies - report them, and remove if not necessary
          for (const cookieName in cookies) {
            const category = getCookieCategory(cookieName);

            if (!category) {
              if (window.cookieConsentSettings?.debug) {
                console.log(
                  "Cookie enforcer: Found unknown cookie",
                  cookieName
                );
              }

              // Apply precautionary principle - remove unknown cookies unless they look like platform cookies
              if (!cookieName.match(/(wordpress|wp_|woocommerce|session)/i)) {
                if (window.cookieConsentSettings?.debug) {
                  console.log(
                    "Cookie enforcer: Removing unknown cookie",
                    cookieName
                  );
                }
                removeCookie(cookieName);
              }
            } else if (category !== "necessary" && !consentData[category]) {
              // Double-check: remove categorized cookies if their category is not allowed
              removeCookie(cookieName);
            }
          }
        } catch (error) {
          console.error("Cookie enforcer: Error processing cookies", error);
        }
      }

      // Run initially
      processCookies();

      // Re-run when consent changes
      document.addEventListener("consentUpdated", function (event) {
        if (window.cookieConsentSettings?.debug) {
          console.log(
            "Cookie enforcer: Consent updated, reprocessing cookies",
            event.detail
          );
        }
        processCookies();
      });

      if (window.cookieConsentSettings?.debug) {
        console.log("Cookie enforcer: Initialized");
      }
    });
  }

  // Initialize when DOM is ready or immediately if already loaded
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

  // Force initialization after a timeout in case the consent manager never emits events
  setTimeout(function () {
    try {
      // Check if we've already initialized
      const event = new CustomEvent("consentManagerReady");
      document.dispatchEvent(event);
    } catch (e) {
      console.error("Cookie enforcer: Error forcing initialization", e);
    }
  }, 2000);
})();
