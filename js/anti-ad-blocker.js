/**
 * Anti-Ad-Blocker Script for Cookie Consent Banner
 *
 * This script detects ad blockers that might block the cookie consent banner
 * and implements countermeasures to ensure the banner is displayed.
 *
 * It uses various techniques to detect blockers and bypass them while
 * maintaining compliance with GDPR and privacy regulations.
 */

(function () {
  "use strict";

  /**
   * Cookie Banner Anti-Blocker Module
   */
  const CookieBannerAntiBlocker = {
    // Configuration
    config: {
      debug: window.cookieConsentSettings?.debug || false,
      checkInterval: 1000, // Check every second
      maxRetries: 5, // Maximum number of retry attempts
      elementSelectors: [
        ".cookie-consent-banner",
        "#cookie-consent-banner",
        ".cookie-settings-trigger",
      ],
      classNameVariations: [
        "consent-ui",
        "cookie-notice",
        "gdpr-banner",
        "privacy-manager",
        "consent-dialog",
      ],
    },

    // State
    state: {
      attempts: 0,
      bannerBlocked: false,
      bannerDetected: false,
      interval: null,
      alternativeBannerDeployed: false,
    },

    /**
     * Initialize the anti-blocker system
     */
    init: function () {
      this.log("Initializing anti-ad-blocker protection for cookie banner");

      // Start monitoring for ad blockers
      this.startMonitoring();

      // Setup detection for common ad blocker behaviors
      this.setupDetectors();

      // Add event listeners
      this.addEventListeners();
    },

    /**
     * Start monitoring the page for signs of banner blocking
     */
    startMonitoring: function () {
      // Clear any existing interval
      if (this.state.interval) {
        clearInterval(this.state.interval);
      }

      // Set up the monitoring interval
      this.state.interval = setInterval(() => {
        // Check if banner exists
        const bannerExists = this.checkBannerExists();

        if (bannerExists) {
          this.state.bannerDetected = true;
          this.stopMonitoring();
          this.log("Cookie banner detected, stopping monitoring");
          return;
        }

        // If banner doesn't exist after init and we haven't detected blocking yet
        if (!this.state.bannerDetected && !this.state.bannerBlocked) {
          this.state.attempts++;
          this.log(
            `Banner not detected, attempt ${this.state.attempts} of ${this.config.maxRetries}`
          );

          // If max retries reached, assume banner is blocked
          if (this.state.attempts >= this.config.maxRetries) {
            this.state.bannerBlocked = true;
            this.handleBlockedBanner();
            this.stopMonitoring();
          }
        }
      }, this.config.checkInterval);
    },

    /**
     * Stop monitoring interval
     */
    stopMonitoring: function () {
      if (this.state.interval) {
        clearInterval(this.state.interval);
        this.state.interval = null;
      }
    },

    /**
     * Check if the cookie banner exists in the DOM
     */
    checkBannerExists: function () {
      // Check for the banner using multiple selectors
      for (const selector of this.config.elementSelectors) {
        if (document.querySelector(selector)) {
          return true;
        }
      }

      // Check for any element that might be the consent banner
      const allDivs = document.querySelectorAll("div, section, aside");
      for (const div of allDivs) {
        const classNames = div.className.toLowerCase();

        // Check if any of our banner class name variations are present
        if (
          this.config.classNameVariations.some((variation) =>
            classNames.includes(variation)
          )
        ) {
          return true;
        }

        // Check for common cookie/consent-related text content
        if (
          div.textContent &&
          (div.textContent.includes("cookie") ||
            div.textContent.includes("consent") ||
            div.textContent.includes("GDPR") ||
            div.textContent.includes("privacy"))
        ) {
          // Check if this element has buttons or links (typical for consent banners)
          if (div.querySelectorAll("button, a").length > 0) {
            return true;
          }
        }
      }

      return false;
    },

    /**
     * Setup various detectors to identify ad blocker behavior
     */
    setupDetectors: function () {
      // Try to create a bait element that ad blockers might hide
      this.createBaitElement();

      // Detect if any scripts are being blocked
      this.detectScriptBlocking();
    },

    /**
     * Create a bait element that ad blockers might target
     */
    createBaitElement: function () {
      const bait = document.createElement("div");
      bait.id = "cookie-consent-detector";
      bait.className = "ad-banner cookie-notice";
      bait.style.cssText =
        "position:absolute; width:1px; height:1px; opacity:0.01; left:-9999px;";
      document.body.appendChild(bait);

      // Check if the bait gets hidden or removed
      setTimeout(() => {
        // Check if bait element was hidden or removed by adblockers
        if (
          !bait ||
          !document.body.contains(bait) ||
          window.getComputedStyle(bait).display === "none" ||
          window.getComputedStyle(bait).visibility === "hidden" ||
          bait.style.display === "none"
        ) {
          this.log("Ad blocker detected (bait element was blocked)");
          this.state.bannerBlocked = true;

          // If monitoring hasn't already detected issues, handle it now
          if (this.state.attempts < this.config.maxRetries) {
            this.handleBlockedBanner();
            this.stopMonitoring();
          }
        }

        // Clean up the bait element
        if (bait && bait.parentNode) {
          bait.parentNode.removeChild(bait);
        }
      }, 500);
    },

    /**
     * Detect if script loading is being blocked
     */
    detectScriptBlocking: function () {
      // Check if key window variables exist
      if (window.bannerTemplate === undefined) {
        this.log("Possible script blocking detected (bannerTemplate missing)");

        // This could be a false positive, so just increment attempts to speed up detection
        this.state.attempts = Math.max(
          this.state.attempts,
          this.config.maxRetries - 1
        );
      }
    },

    /**
     * Handle case where banner is blocked
     */
    handleBlockedBanner: function () {
      if (this.state.alternativeBannerDeployed) {
        return; // Prevent multiple deployments
      }

      this.log("Deploying alternative cookie banner due to detected blocking");

      // Try an alternative method to display the banner
      this.deployAlternativeBanner();

      // Mark as deployed
      this.state.alternativeBannerDeployed = true;
    },

    /**
     * Deploy an alternative banner using techniques to avoid blockers
     * Uses different class names, inline styles, and dynamic naming
     */
    deployAlternativeBanner: function () {
      // Try to get the banner HTML from window.bannerTemplate, or create a minimal version
      let bannerHtml = window.bannerTemplate || this.getMinimalBannerHtml();

      // Create container with non-blocked class names
      const container = document.createElement("div");

      // Use a random ID to avoid pattern matching by blockers
      const randomId =
        "privacy-ui-" + Math.random().toString(36).substring(2, 10);
      container.id = randomId;

      // Use neutral class names unlikely to be blocked
      container.className = "privacy-ui dialog-component user-feedback";

      // Use inline styles to ensure visibility
      container.style.cssText =
        "position:fixed; bottom:0; left:0; right:0; background:#fff; box-shadow:0 -2px 10px rgba(0,0,0,0.1); z-index:999999; padding:15px; font-family:sans-serif; font-size:14px;";

      // Add the banner content (either from template or minimal version)
      if (typeof bannerHtml === "string") {
        container.innerHTML = bannerHtml;
      } else {
        container.appendChild(bannerHtml);
      }

      // Try to modify any class names within to avoid detection
      this.obfuscateClassNames(container);

      // Add the container to the DOM
      document.body.appendChild(container);

      // Add event listeners to the buttons
      this.setupAlternativeBannerHandlers(container);
    },

    /**
     * Get minimal banner HTML as a fallback
     */
    getMinimalBannerHtml: function () {
      // Create minimal but compliant banner
      const banner = document.createElement("div");

      // Banner title
      const title = document.createElement("h3");
      title.textContent = "This website uses cookies";
      title.style.cssText = "margin:0 0 10px; font-size:16px;";
      banner.appendChild(title);

      // Banner text
      const description = document.createElement("p");
      description.textContent =
        "We use cookies to ensure basic functionality and improve your experience.";
      description.style.cssText = "margin:0 0 15px;";
      banner.appendChild(description);

      // Button container
      const buttonContainer = document.createElement("div");
      buttonContainer.style.cssText = "display:flex; gap:10px;";

      // Accept button
      const acceptButton = document.createElement("button");
      acceptButton.textContent = "Accept All";
      acceptButton.className = "accept-all-button";
      acceptButton.style.cssText =
        "background:#3939CC; color:#fff; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;";
      buttonContainer.appendChild(acceptButton);

      // Reject button
      const rejectButton = document.createElement("button");
      rejectButton.textContent = "Reject Non-Essential";
      rejectButton.className = "reject-button";
      rejectButton.style.cssText =
        "background:#f0f0f0; color:#333; border:1px solid #ccc; padding:8px 15px; border-radius:4px; cursor:pointer;";
      buttonContainer.appendChild(rejectButton);

      // Settings button
      const settingsButton = document.createElement("button");
      settingsButton.textContent = "Cookie Settings";
      settingsButton.className = "settings-button";
      settingsButton.style.cssText =
        "background:transparent; color:#3939CC; border:1px solid #3939CC; padding:8px 15px; border-radius:4px; cursor:pointer;";
      buttonContainer.appendChild(settingsButton);

      banner.appendChild(buttonContainer);

      return banner;
    },

    /**
     * Obfuscate class names to avoid detection
     */
    obfuscateClassNames: function (container) {
      // Replace class names that might be blocked
      const elementsToChange = container.querySelectorAll(
        '[class*="cookie"], [class*="consent"], [class*="gdpr"]'
      );

      elementsToChange.forEach((element) => {
        // Generate a random class name
        const randomClass =
          "ui-component-" + Math.random().toString(36).substring(2, 8);

        // Replace the class
        if (element.className) {
          element.className = element.className
            .replace(/cookie/gi, randomClass)
            .replace(/consent/gi, "dialog")
            .replace(/gdpr/gi, "notices");
        }
      });
    },

    /**
     * Setup event handlers for the alternative banner
     */
    setupAlternativeBannerHandlers: function (container) {
      // Find buttons by text content since we don't know exact class names
      const allButtons = container.querySelectorAll("button");

      allButtons.forEach((button) => {
        const text = button.textContent.toLowerCase();

        button.addEventListener("click", (e) => {
          e.preventDefault();

          // Accept button handler
          if (text.includes("accept") || button.className.includes("accept")) {
            this.log("Accept button clicked on alternative banner");
            this.acceptAllConsent();
          }

          // Reject button handler
          else if (
            text.includes("reject") ||
            text.includes("decline") ||
            button.className.includes("reject")
          ) {
            this.log("Reject button clicked on alternative banner");
            this.rejectNonEssentialConsent();
          }

          // Settings button handler
          else if (
            text.includes("settings") ||
            text.includes("preference") ||
            button.className.includes("settings")
          ) {
            this.log("Settings button clicked on alternative banner");
            this.showConsentSettings();
          }

          // Hide the banner after interaction
          if (container && container.parentNode) {
            container.parentNode.removeChild(container);
          }
        });
      });
    },

    /**
     * Accept all consent categories
     */
    acceptAllConsent: function () {
      // If consentManager is available, use it
      if (window.consentManager) {
        window.consentManager.acceptAll();
        return;
      }

      // Fallback if consent manager is not available directly
      const consentData = {
        version: "1.0.0",
        timestamp: new Date().toISOString(),
        categories: {
          necessary: true,
          analytics: true,
          functional: true,
          marketing: true,
        },
      };

      // Store consent data in localStorage and cookie
      try {
        localStorage.setItem(
          "devora_cookie_consent",
          JSON.stringify(consentData)
        );
        this.setCookie(
          "devora_cookie_consent",
          JSON.stringify(consentData),
          365
        );

        // Dispatch consent event for other scripts
        const consentEvent = new CustomEvent("consentUpdated", {
          detail: consentData.categories,
        });
        window.dispatchEvent(consentEvent);

        // Update Google consent if dataLayer is available
        if (window.dataLayer) {
          window.dataLayer.push([
            "consent",
            "update",
            {
              ad_storage: "granted",
              analytics_storage: "granted",
              functionality_storage: "granted",
              personalization_storage: "granted",
              security_storage: "granted",
              ad_user_data: "granted",
              ad_personalization: "granted",
            },
          ]);
        }
      } catch (e) {
        this.log("Error saving consent data: " + e.message);
      }
    },

    /**
     * Reject non-essential consent categories
     */
    rejectNonEssentialConsent: function () {
      // If consentManager is available, use it
      if (window.consentManager) {
        window.consentManager.declineAll();
        return;
      }

      // Fallback if consent manager is not available directly
      const consentData = {
        version: "1.0.0",
        timestamp: new Date().toISOString(),
        categories: {
          necessary: true,
          analytics: false,
          functional: false,
          marketing: false,
        },
      };

      // Store consent data in localStorage and cookie
      try {
        localStorage.setItem(
          "devora_cookie_consent",
          JSON.stringify(consentData)
        );
        this.setCookie(
          "devora_cookie_consent",
          JSON.stringify(consentData),
          365
        );

        // Dispatch consent event for other scripts
        const consentEvent = new CustomEvent("consentUpdated", {
          detail: consentData.categories,
        });
        window.dispatchEvent(consentEvent);

        // Update Google consent if dataLayer is available
        if (window.dataLayer) {
          window.dataLayer.push([
            "consent",
            "update",
            {
              ad_storage: "denied",
              analytics_storage: "denied",
              functionality_storage: "denied",
              personalization_storage: "denied",
              security_storage: "granted",
              ad_user_data: "denied",
              ad_personalization: "denied",
            },
          ]);
        }
      } catch (e) {
        this.log("Error saving consent data: " + e.message);
      }
    },

    /**
     * Show consent settings UI
     */
    showConsentSettings: function () {
      // If we can find the main consent manager, use it
      if (
        window.consentManager &&
        typeof window.consentManager.showConsentBanner === "function"
      ) {
        window.consentManager.showConsentBanner();
        return;
      }

      // Fallback - try to find existing settings button and click it
      const settingsButtons = document.querySelectorAll(
        '.cookie-settings-trigger, [data-action="cookie-settings"]'
      );
      if (settingsButtons.length > 0) {
        settingsButtons[0].click();
        return;
      }

      // Last resort - redirect to privacy policy page if available
      if (
        window.cookieConsentSettings &&
        window.cookieConsentSettings.privacyUrl
      ) {
        window.location.href = window.cookieConsentSettings.privacyUrl;
      }
    },

    /**
     * Set a cookie with the given name, value and days until expiration
     */
    setCookie: function (name, value, days) {
      const date = new Date();
      date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
      const expires = "; expires=" + date.toUTCString();
      document.cookie =
        name +
        "=" +
        encodeURIComponent(value) +
        expires +
        "; path=/; SameSite=Lax";
    },

    /**
     * Add event listeners for various scenarios
     */
    addEventListeners: function () {
      // Check for banner blocking after load completes
      window.addEventListener("load", () => {
        // Wait a bit after load to allow banner to appear normally
        setTimeout(() => {
          if (!this.state.bannerDetected && !this.state.bannerBlocked) {
            this.state.attempts = this.config.maxRetries;
            this.state.bannerBlocked = true;
            this.handleBlockedBanner();
            this.stopMonitoring();
          }
        }, 2000);
      });

      // If the page visibility changes, recheck for banner
      document.addEventListener("visibilitychange", () => {
        if (
          document.visibilityState === "visible" &&
          !this.state.bannerDetected &&
          !this.state.bannerBlocked
        ) {
          this.startMonitoring();
        }
      });
    },

    /**
     * Log messages when debug is enabled
     */
    log: function (message) {
      if (this.config.debug) {
        console.log("[CookieBannerAntiBlocker]", message);
      }
    },
  };

  // Initialize the anti-blocker if enabled in settings
  if (
    window.cookieConsentSettings &&
    window.cookieConsentSettings.enableAntiBlocker
  ) {
    // Wait for DOM to be ready
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => {
        CookieBannerAntiBlocker.init();
      });
    } else {
      CookieBannerAntiBlocker.init();
    }
  }
})();
