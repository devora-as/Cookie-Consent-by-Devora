// Check if ConsentManager has already been defined to prevent duplicate declarations
if (typeof window.ConsentManager === "undefined") {
  // Define the ConsentManager class only once
  window.ConsentManager = class ConsentManager {
    constructor() {
      this.storageKey = "devora_cookie_consent";
      this.consentVersion = "1.0.0";

      // Check if the current user agent is a bot (use server-side detection if available)
      this.isBot = window.customCookieConsent?.isBot || this.detectBot();

      // If it's a bot, automatically set consent
      if (this.isBot) {
        this.setFullConsentForBot();
        return;
      }

      // Use requestIdleCallback for non-critical initialization
      if (window.requestIdleCallback) {
        window.requestIdleCallback(() => this.deferInit(), { timeout: 1000 });
      } else {
        // Fallback to setTimeout with a small delay to not block main thread
        setTimeout(() => this.deferInit(), 50);
      }
    }

    /**
     * Detects if the current user agent is a bot/crawler
     * @returns {boolean} True if the user agent is a bot
     */
    detectBot() {
      const botPatterns = [
        "googlebot",
        "bingbot",
        "yandexbot",
        "duckduckbot",
        "slurp",
        "baiduspider",
        "facebookexternalhit",
        "linkedinbot",
        "twitterbot",
        "applebot",
        "msnbot",
        "aolbuild",
        "yahoo",
        "teoma",
        "sogou",
        "exabot",
        "facebot",
        "ia_archiver",
        "semrushbot",
        "ahrefsbot",
        "mj12bot",
        "seznambot",
        "yeti",
        "naverbot",
        "crawler",
        "spider",
        "mediapartners-google",
        "adsbot-google",
        "feedfetcher",
        "bot",
        "crawl",
        "slurp",
        "spider",
        "mediapartners",
        "lighthouse",
      ];

      const userAgent = navigator.userAgent.toLowerCase();
      return botPatterns.some((pattern) => userAgent.includes(pattern));
    }

    /**
     * Sets full consent for bots without showing the banner
     */
    setFullConsentForBot() {
      // Create consent data with all categories accepted
      const categories = {
        necessary: true,
        analytics: true,
        functional: true,
      };

      const consentData = {
        version: this.consentVersion,
        timestamp: new Date().toISOString(),
        categories: categories,
        isBot: true,
      };

      // Store the consent data
      try {
        localStorage.setItem(this.storageKey, JSON.stringify(consentData));
      } catch (e) {
        // Ignore localStorage errors for bots
      }

      // Apply the consent settings
      this.applyConsentForBot(categories);
    }

    /**
     * Applies consent settings for bots
     * @param {Object} categories The consent categories
     */
    applyConsentForBot(categories) {
      // Set up dataLayer with consent granted for all categories
      window.dataLayer = window.dataLayer || [];
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

    deferInit() {
      // Skip for bots - they already have consent set
      if (this.isBot) {
        return;
      }

      // Set default consent state immediately
      this.setDefaultConsent();

      // Always set up the triggers, even if the banner won't be shown initially
      this.setupTriggers();

      // Only show banner and load template if no consent yet
      if (!this.hasValidConsent()) {
        // Use performance-optimized banner display
        this.delayBannerDisplay();
      }

      // Apply existing consent if any
      this.applyConsent();
    }

    setDefaultConsent() {
      // Check if dataLayer already has consent settings (from head)
      let consentAlreadySet = false;

      if (window.dataLayer && Array.isArray(window.dataLayer)) {
        consentAlreadySet = window.dataLayer.some((item) => {
          return (
            Array.isArray(item) &&
            item[0] === "consent" &&
            item[1] === "default"
          );
        });
      }

      // Skip setting default consent if already set in head
      if (consentAlreadySet) {
        if (window.cookieConsentSettings?.debug) {
          console.log(
            "Default consent already set in head, skipping duplicate initialization"
          );
        }
        return;
      }

      if (!window.dataLayer) {
        window.dataLayer = [];
      }

      // Create default consent settings with all types denied by default
      // (except security_storage which is always granted)
      const defaultConsent = {
        // Analytics storage is denied by default (requires analytics consent)
        analytics_storage: "denied",

        // Functional storage is denied by default (requires functional consent)
        functionality_storage: "denied",
        personalization_storage: "denied",

        // Security storage is always granted (necessary cookies)
        security_storage: "granted",

        // Marketing-related settings are denied by default (requires marketing consent)
        ad_storage: "denied",
        ad_user_data: "denied",
        ad_personalization: "denied",

        wait_for_update: 500, // Reduced from 2000
      };

      // Get region setting from cookieConsentSettings if available
      if (
        window.cookieConsentSettings &&
        window.cookieConsentSettings.consent_region
      ) {
        if (window.cookieConsentSettings.consent_region === "NO") {
          defaultConsent.region = ["NO"];
        } else if (window.cookieConsentSettings.consent_region === "EEA") {
          // EEA countries list - all EU countries plus Norway, Iceland, and Liechtenstein
          defaultConsent.region = [
            "AT",
            "BE",
            "BG",
            "HR",
            "CY",
            "CZ",
            "DK",
            "EE",
            "FI",
            "FR",
            "DE",
            "GR",
            "HU",
            "IE",
            "IT",
            "LV",
            "LT",
            "LU",
            "MT",
            "NL",
            "PL",
            "PT",
            "RO",
            "SK",
            "SI",
            "ES",
            "SE", // EU countries
            "NO",
            "IS",
            "LI",
            "GB", // Norway, Iceland, Liechtenstein, UK
          ];
        }
        // For 'GLOBAL', we don't set any region which applies restrictions globally
      } else {
        // Default to NO if not configured
        defaultConsent.region = ["NO"];
      }

      // Log default consent for debugging
      if (window.cookieConsentSettings?.debug) {
        console.log("Setting default consent mode:", defaultConsent);
      }

      window.dataLayer.push(["consent", "default", defaultConsent]);
    }

    delayBannerDisplay() {
      // Check if banner template is available when needed
      if (!window.bannerTemplate) {
        // If template not available, wait for it with a timeout
        const maxWaitTime = 2000; // 2 seconds max wait
        const checkInterval = 100; // Check every 100ms
        let waitTime = 0;

        const templateChecker = setInterval(() => {
          waitTime += checkInterval;
          if (window.bannerTemplate) {
            clearInterval(templateChecker);
            this.scheduleOptimalBannerDisplay();
          } else if (waitTime >= maxWaitTime) {
            // Give up after max wait time
            clearInterval(templateChecker);
          }
        }, checkInterval);

        return;
      }

      this.scheduleOptimalBannerDisplay();
    }

    scheduleOptimalBannerDisplay() {
      // Use different strategies based on browser capabilities
      if ("requestIdleCallback" in window) {
        // Use requestIdleCallback for modern browsers
        requestIdleCallback(
          () => {
            this.showConsentBanner();
          },
          { timeout: 3000 }
        ); // 3 second timeout
      } else if ("PerformanceObserver" in window) {
        // Use PerformanceObserver for browsers that support it
        const observer = new PerformanceObserver((list) => {
          const entries = list.getEntries();
          if (entries.length > 0) {
            const lcpTime = entries[0].startTime;
            // Wait for LCP + small delay
            setTimeout(
              () => this.showConsentBanner(),
              Math.max(0, lcpTime + 100)
            );
            observer.disconnect();
          }
        });

        try {
          observer.observe({ entryTypes: ["largest-contentful-paint"] });

          // Fallback if LCP doesn't fire within 2 seconds
          setTimeout(() => {
            if (!document.querySelector(".cookie-consent-banner")) {
              this.showConsentBanner();
              observer.disconnect();
            }
          }, 2000);
        } catch (e) {
          // Fallback for browsers that don't support LCP observation
          setTimeout(() => this.showConsentBanner(), 1000);
        }
      } else {
        // Fallback for older browsers - delay banner by 1 second
        setTimeout(() => this.showConsentBanner(), 1000);
      }
    }

    init() {
      // Set initial default consent state
      window.dataLayer = window.dataLayer || [];

      // Create a helper function to push to dataLayer
      const pushToDataLayer = (...args) => {
        window.dataLayer.push(...args);
      };

      // Ensure this runs before GTM loads
      // Create default consent settings with all types denied by default
      // (except security_storage which is always granted)
      const defaultConsent = {
        // Analytics storage is denied by default (requires analytics consent)
        analytics_storage: "denied",

        // Functional storage is denied by default (requires functional consent)
        functionality_storage: "denied",
        personalization_storage: "denied",

        // Security storage is always granted (necessary cookies)
        security_storage: "granted",

        // Marketing-related settings are denied by default (requires marketing consent)
        ad_storage: "denied",
        ad_user_data: "denied",
        ad_personalization: "denied",

        wait_for_update: 2000,
      };

      // Get region setting from cookieConsentSettings if available
      if (
        window.cookieConsentSettings &&
        window.cookieConsentSettings.consent_region
      ) {
        if (window.cookieConsentSettings.consent_region === "NO") {
          defaultConsent.region = ["NO"];
        } else if (window.cookieConsentSettings.consent_region === "EEA") {
          // EEA countries list - all EU countries plus Norway, Iceland, and Liechtenstein
          defaultConsent.region = [
            "AT",
            "BE",
            "BG",
            "HR",
            "CY",
            "CZ",
            "DK",
            "EE",
            "FI",
            "FR",
            "DE",
            "GR",
            "HU",
            "IE",
            "IT",
            "LV",
            "LT",
            "LU",
            "MT",
            "NL",
            "PL",
            "PT",
            "RO",
            "SK",
            "SI",
            "ES",
            "SE", // EU countries
            "NO",
            "IS",
            "LI",
            "GB", // Norway, Iceland, Liechtenstein, UK
          ];
        }
        // For 'GLOBAL', we don't set any region which applies restrictions globally
      } else {
        // Default to NO if not configured
        defaultConsent.region = ["NO"];
      }

      // Log default consent for debugging
      if (window.cookieConsentSettings?.debug) {
        console.log("Setting default consent mode in init:", defaultConsent);
      }

      pushToDataLayer(["consent", "default", defaultConsent]);

      // Enable URL passthrough and ads redaction
      pushToDataLayer(["set", "url_passthrough", true]);
      pushToDataLayer(["set", "ads_data_redaction", true]);

      // Initialize GTM after setting default consent
      const gtmId = window.cookieConsentConfig?.gtmId;

      if (gtmId) {
        (function (w, d, s, l, i) {
          w[l] = w[l] || [];
          w[l].push({ "gtm.start": new Date().getTime(), event: "gtm.js" });
          var f = d.getElementsByTagName(s)[0],
            j = d.createElement(s),
            dl = l != "dataLayer" ? "&l=" + l : "";
          j.async = true;
          j.src = "https://www.googletagmanager.com/gtm.js?id=" + i + dl;
          f.parentNode.insertBefore(j, f);
        })(window, document, "script", "dataLayer", gtmId);
      }

      // Setup click handlers for cookie settings links
      this.setupTriggers();

      // Setup banner immediately if no consent
      if (!this.hasValidConsent()) {
        if (document.readyState === "loading") {
          document.addEventListener("DOMContentLoaded", () =>
            this.showConsentBanner()
          );
        } else {
          this.showConsentBanner();
        }
      }

      // Apply any existing consent
      this.applyConsent();

      // Add global error handler for consent operations
      window.addEventListener("error", (event) => {
        if (event.error?.message?.includes("consent")) {
          // Error handling for consent operations
        }
      });
    }

    setupTriggers() {
      // Add click handler for settings link
      const setupLinks = () => {
        document
          .querySelectorAll(".cookie-settings-trigger")
          .forEach((trigger) => {
            trigger.addEventListener("click", (e) => {
              e.preventDefault();
              e.stopPropagation();
              this.showConsentBanner();
            });
          });
      };

      // Run initial setup
      setupLinks();

      // Also run setup when content is loaded
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", setupLinks);
      }

      // Add event listener for dynamically added triggers
      document.addEventListener("click", (e) => {
        if (e.target.closest(".cookie-settings-trigger")) {
          e.preventDefault();
          e.stopPropagation();
          this.showConsentBanner();
        }
      });
    }

    showConsentBanner() {
      // Check if banner template is available when needed
      if (!window.bannerTemplate) {
        console.error("Banner template not found");
        return;
      }

      // First remove any existing banner
      const existingBanner = document.querySelector(".cookie-consent-banner");
      if (existingBanner) {
        existingBanner.remove();
      }

      // Remove any existing overlay
      const existingOverlay = document.querySelector(".cookie-consent-overlay");
      if (existingOverlay) {
        existingOverlay.remove();
      }

      // Store the active element to restore focus later
      this.previouslyFocusedElement = document.activeElement;

      // Use document fragment for better performance
      const fragment = document.createDocumentFragment();
      const bannerDiv = document.createElement("div");
      bannerDiv.innerHTML = window.bannerTemplate;
      fragment.appendChild(bannerDiv.firstElementChild);

      // Append to body
      document.body.appendChild(fragment);

      // Get the banner element
      const banner = document.querySelector(".cookie-consent-banner");
      if (!banner) {
        console.error("Banner element not found after appending to DOM");
        return;
      }

      // Get position from data attribute, settings, or default to bottom
      let position =
        banner.dataset.position || window.cookieSettings?.position || "bottom";

      // Validate position value - ensure it's one of the valid options
      if (!["bottom", "top", "center"].includes(position)) {
        console.warn(
          `Invalid position value: ${position}. Defaulting to bottom.`
        );
        position = "bottom";
      }

      if (window.cookieSettings?.debugMode) {
        console.log("Banner position:", position);
      }

      // Remove any existing position classes
      banner.classList.remove(
        "position-bottom",
        "position-top",
        "position-center"
      );

      // Add position class
      banner.classList.add(`position-${position}`);

      // Create overlay for center/modal position
      if (position === "center") {
        const overlay = document.createElement("div");
        overlay.className = "cookie-consent-overlay";
        document.body.appendChild(overlay);

        // First set display to block (it starts hidden in CSS)
        overlay.style.display = "block";

        // Force a reflow before adding the visible class to ensure animation works
        overlay.offsetHeight;

        // Add visible class to trigger animation
        setTimeout(() => {
          overlay.classList.add("visible");
        }, 10);
      }

      // Apply critical inline styles to ensure correct display
      if (banner) {
        // Style the toggle switches
        const toggles = banner.querySelectorAll(".toggle-switch");
        toggles.forEach((toggle) => {
          toggle.style.position = "relative";
          toggle.style.display = "inline-block";
          toggle.style.width = "44px";
          toggle.style.height = "22px";
          toggle.style.flexShrink = "0";
          toggle.style.marginTop = "2px";
        });

        // Style the toggle sliders
        const sliders = banner.querySelectorAll(".slider");
        sliders.forEach((slider) => {
          slider.style.position = "absolute";
          slider.style.cursor = "pointer";
          slider.style.top = "0";
          slider.style.left = "0";
          slider.style.right = "0";
          slider.style.bottom = "0";
          slider.style.backgroundColor = "#999";
          slider.style.borderRadius = "22px";
        });

        // Style the buttons
        const buttons = banner.querySelectorAll("button");
        buttons.forEach((button) => {
          button.style.borderRadius = "4px";
          button.style.padding = "0.75rem 1.5rem";
          button.style.cursor = "pointer";
          button.style.fontWeight = "500";
        });

        // Style accept button
        const acceptButton = banner.querySelector(".cookie-consent-accept");
        if (acceptButton) {
          acceptButton.style.backgroundColor = "#3939CC";
          acceptButton.style.color = "white";
          acceptButton.style.border = "none";
        }

        // Style decline button
        const declineButton = banner.querySelector(".cookie-consent-decline");
        if (declineButton) {
          declineButton.style.backgroundColor = "#f0f0f0";
          declineButton.style.color = "#222";
          declineButton.style.border = "1px solid #ccc";
        }

        // Style save custom button
        const saveButton = banner.querySelector(".cookie-consent-save-custom");
        if (saveButton) {
          saveButton.style.backgroundColor = "#e0e0fd";
          saveButton.style.color = "#222";
          saveButton.style.border = "1px solid #3939CC";
        }

        // Style the close button
        const closeButton = banner.querySelector(".cookie-consent-close");
        if (closeButton) {
          closeButton.style.position = "absolute";
          closeButton.style.top = "0.75rem";
          closeButton.style.right = "0.75rem";
          closeButton.style.background = "none";
          closeButton.style.border = "1px solid transparent";
          closeButton.style.fontSize = "1rem";
          closeButton.style.padding = "0.5rem";
          closeButton.style.cursor = "pointer";
          closeButton.style.color = "#444";
        }

        // Style branding
        const branding = banner.querySelector(".cookie-consent-branding");
        if (branding) {
          branding.style.marginTop = "0.75rem";
          branding.style.fontSize = "0.75rem";
          branding.style.opacity = "0.9";
        }

        const devoraName = banner.querySelector(".devora-name");
        if (devoraName) {
          devoraName.style.color = "#3939CC";
          devoraName.style.fontWeight = "600";
        }
      }

      // Use requestAnimationFrame for smoother animation
      requestAnimationFrame(() => {
        // Add visible class in next frame to allow transition to work
        requestAnimationFrame(() => {
          if (banner) {
            banner.classList.add("visible");
          }
        });
      });

      // Set up focus trap
      this.setupFocusTrap(banner);

      // Use event delegation for better performance
      banner.addEventListener("click", (e) => {
        const target = e.target;

        // Handle accept button
        if (target.closest(".cookie-consent-accept")) {
          this.acceptAll();
        }

        // Handle decline button
        else if (target.closest(".cookie-consent-decline")) {
          this.declineAll();
        }

        // Handle close button
        else if (target.closest(".cookie-consent-close")) {
          this.hideBanner();
        }

        // Handle save custom button
        else if (target.closest(".cookie-consent-save-custom")) {
          this.saveCustomPreferences();
        }
      });

      // Add click handler for overlay (for center/modal position)
      if (position === "center") {
        const overlay = document.querySelector(".cookie-consent-overlay");
        if (overlay) {
          overlay.addEventListener("click", () => {
            this.hideBanner();
          });
        }
      }

      // Add keyboard event listener for Escape key
      document.addEventListener("keydown", this.handleEscapeKey);

      // Load saved preferences if they exist
      const consent = this.getStoredConsent();
      if (consent && consent.categories) {
        Object.entries(consent.categories).forEach(([category, value]) => {
          const checkbox = document.querySelector(
            `input[data-category="${category}"]`
          );
          if (checkbox && !checkbox.disabled) {
            checkbox.checked = value;
          }
        });
      }

      // Set initial focus to the first interactive element (close button)
      const closeButton = document.querySelector(".cookie-consent-close");
      if (closeButton) {
        // Delay focus to avoid layout shifts during animation
        setTimeout(() => {
          closeButton.focus();
        }, 100);
      }
    }

    // Handle Escape key press
    handleEscapeKey = (event) => {
      if (event.key === "Escape") {
        this.hideBanner();
      }
    };

    // Set up focus trap within the banner
    setupFocusTrap(bannerElement) {
      if (!bannerElement) return;

      // Get all focusable elements
      const focusableElements = bannerElement.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );

      if (focusableElements.length === 0) return;

      const firstElement = focusableElements[0];
      const lastElement = focusableElements[focusableElements.length - 1];

      // Handle tab key to trap focus
      bannerElement.addEventListener("keydown", (event) => {
        if (event.key === "Tab") {
          // Shift + Tab
          if (event.shiftKey) {
            if (document.activeElement === firstElement) {
              lastElement.focus();
              event.preventDefault();
            }
          }
          // Tab
          else {
            if (document.activeElement === lastElement) {
              firstElement.focus();
              event.preventDefault();
            }
          }
        }
      });
    }

    hideBanner() {
      const banner = document.querySelector(".cookie-consent-banner");
      if (!banner) {
        return;
      }

      // Determine position from classes or data attribute
      let position = "bottom";
      if (banner.classList.contains("position-top")) {
        position = "top";
      } else if (banner.classList.contains("position-center")) {
        position = "center";
      } else if (banner.dataset.position) {
        position = banner.dataset.position;
      }

      // Handle overlay for center/modal position
      const overlay = document.querySelector(".cookie-consent-overlay");
      if (position === "center" && overlay) {
        overlay.classList.remove("visible");
        // Add a slight delay to fade out the overlay
        setTimeout(() => {
          if (overlay && overlay.parentNode) {
            overlay.style.display = "none";
          }
        }, 300); // Match this timing with your CSS transition duration
      }

      // Remove visible class to trigger exit animation
      banner.classList.remove("visible");

      // Restore focus to previously focused element
      if (this.previouslyFocusedElement) {
        // Delay focus restoration until after animation completes
        setTimeout(() => {
          if (this.previouslyFocusedElement) {
            this.previouslyFocusedElement.focus();
            this.previouslyFocusedElement = null;
          }
        }, 400);
      }

      // Remove banner and overlay after animation completes
      setTimeout(() => {
        if (banner && banner.parentNode) {
          banner.remove();
        }
        if (overlay && overlay.parentNode) {
          overlay.remove();
        }
      }, 400); // Match this timing with your CSS transition duration

      // Remove keyboard event listener
      document.removeEventListener("keydown", this.handleEscapeKey);
    }

    hasValidConsent() {
      const stored = localStorage.getItem(this.storageKey);
      if (!stored) return false;

      try {
        const consent = JSON.parse(stored);
        return consent.version === this.consentVersion;
      } catch (e) {
        return false;
      }
    }

    getStoredConsent() {
      // Try cookie first
      const cookieValue = document.cookie
        .split("; ")
        .find((row) => row.startsWith(this.storageKey))
        ?.split("=")[1];

      if (cookieValue) {
        try {
          const decoded = decodeURIComponent(cookieValue);
          return JSON.parse(decoded);
        } catch (e) {
          // Error handling for cookie parsing
        }
      }

      // Fallback to localStorage
      try {
        const stored = localStorage.getItem(this.storageKey);
        return stored ? JSON.parse(stored) : null;
      } catch (e) {
        // Error handling for localStorage parsing
        return null;
      }
    }

    acceptAll() {
      // Accept all categories by finding all toggles
      const categories = {
        necessary: true, // Always enabled
        analytics: true, // Explicitly set all main categories
        functional: true,
        marketing: true,
      };

      // Find all category toggles and set them to true
      document
        .querySelectorAll('.cookie-category input[type="checkbox"]')
        .forEach((checkbox) => {
          const category = checkbox.dataset.category;
          categories[category] = true;

          // Update the UI
          checkbox.checked = true;
        });

      // Debug log what categories are being accepted
      if (window.cookieConsentSettings?.debug) {
        console.log("Accept all categories:", categories);
      }

      this.saveConsent(categories);
    }

    declineAll() {
      // Only necessary cookies are allowed, all others are declined
      const categories = {
        necessary: true, // Always needed
      };

      // Find all category toggles and set them to false (except necessary)
      document
        .querySelectorAll('.cookie-category input[type="checkbox"]')
        .forEach((checkbox) => {
          const category = checkbox.dataset.category;
          if (category !== "necessary") {
            categories[category] = false;
            // Update the UI
            checkbox.checked = false;
          }
        });

      this.saveConsent(categories);
    }

    saveCustomPreferences() {
      // Start with necessary cookies (always required)
      const categories = {
        necessary: true,
      };

      // Get all toggles and their states
      document
        .querySelectorAll('.cookie-category input[type="checkbox"]')
        .forEach((checkbox) => {
          const category = checkbox.dataset.category;
          if (category !== "necessary") {
            categories[category] = checkbox.checked;
          }
        });

      this.saveConsent(categories);
    }

    saveConsent(categories) {
      // Record consent timestamp
      const timestamp = new Date().toISOString();

      // Ensure categories object has expected structure
      if (
        !categories.marketing &&
        document.getElementById("marketing-cookie-toggle")
      ) {
        categories.marketing = document.getElementById(
          "marketing-cookie-toggle"
        ).checked;
      }

      const consentData = {
        version: this.consentVersion,
        timestamp: timestamp,
        categories: categories,
      };

      // Debug log the final consent data that will be saved
      if (window.cookieConsentSettings?.debug) {
        console.log("Saving consent data:", consentData);
      }

      // Store in cookie with secure flag if possible
      this.setConsentCookie(consentData);

      // Store server-side (for logged-in users) if possible
      this.sendConsentToServer(consentData);

      // Hide banner after consent is saved
      this.hideBanner();

      // Apply consent immediately
      this.applyConsent();

      // Dispatch consent updated event
      this.dispatchConsentUpdate(consentData);
    }

    /**
     * Send consent data to server for logged-in users
     *
     * @param {Object} consentData The consent data to send
     */
    sendConsentToServer(consentData) {
      // Only proceed if we have the admin-ajax URL
      if (!window.cookieConsentSettings?.ajaxUrl) {
        this.debugLog("No AJAX URL found for consent storage");
        return;
      }

      this.debugLog("Sending consent data to server:", {
        ajaxUrl: window.cookieConsentSettings.ajaxUrl,
        consentData: consentData,
      });

      // Send consent data to server
      async function sendConsentToServer(consentData) {
        try {
          const formData = new FormData();
          formData.append("action", "save_cookie_consent");
          formData.append("consent_data", JSON.stringify(consentData));
          formData.append("nonce", window.cookieConsentSettings?.nonce || "");

          const response = await fetch(
            window.cookieConsentSettings?.ajaxurl || "/wp-admin/admin-ajax.php",
            {
              method: "POST",
              body: formData,
              credentials: "same-origin",
            }
          );

          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }

          const data = await response.json();
          if (!data.success) {
            throw new Error(data.data || "Failed to save consent data");
          }

          return data;
        } catch (error) {
          console.error("Error saving consent data:", error);
          // Don't throw the error, just log it
          return { success: false, error: error.message };
        }
      }

      // Save consent data
      async function saveConsent(consentData) {
        try {
          // Save to localStorage first
          localStorage.setItem(STORAGE_KEY, JSON.stringify(consentData));

          // Then try to save to server
          const result = await sendConsentToServer(consentData);

          if (!result.success) {
            console.warn("Failed to save consent to server:", result.error);
            // Continue with local storage only
          }

          return true;
        } catch (error) {
          console.error("Error saving consent:", error);
          return false;
        }
      }

      // Create separate form data for consent logging
      const logFormData = new FormData();
      logFormData.append("action", "custom_cookie_log_consent");
      logFormData.append(
        "nonce",
        document.querySelector('meta[name="cookie_consent_nonce"]')?.content ||
          ""
      );
      logFormData.append("consent_data", JSON.stringify(consentData));
      logFormData.append("source", this.getConsentSource());

      // Send the data to the logging endpoint
      fetch(window.cookieConsentSettings.ajaxUrl, {
        method: "POST",
        credentials: "same-origin",
        body: logFormData,
      })
        .then((response) => response.json())
        .then((data) => {
          this.debugLog("Consent logging response:", data);
        })
        .catch((error) => {
          console.error("Error logging consent data:", error);
        });
    }

    /**
     * Determine the source of the consent action
     *
     * @returns {string} The source of the consent (banner, settings, link, etc.)
     */
    getConsentSource() {
      // Check if consent was given through the settings panel
      if (document.querySelector(".cookie-settings-view.active")) {
        return "settings";
      }

      // Check if consent was given through the banner
      if (document.querySelector(".cookie-consent-banner:not(.hidden)")) {
        return "banner";
      }

      return "other";
    }

    // Emergency fallback method if cookie setting fails
    emergencySaveCookie(consentData) {
      // Try with minimal attributes approach
      const cookieValue = encodeURIComponent(JSON.stringify(consentData));
      document.cookie = `${this.storageKey}=${cookieValue}; path=/; max-age=31536000`;

      // Apply consent immediately without reload
      this.applyConsent();

      // Force update for analytics and marketing with proper separation
      if (
        consentData.categories.analytics &&
        !consentData.categories.marketing
      ) {
        // If only analytics consent is given (not marketing)
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push([
          "consent",
          "update",
          {
            analytics_storage: "granted",
            // Keep marketing/ads denied
            ad_storage: "denied",
            ad_user_data: "denied",
            ad_personalization: "denied",
          },
        ]);
      } else if (consentData.categories.marketing) {
        // If marketing consent is given (which implies analytics consent too in most cases)
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push([
          "consent",
          "update",
          {
            analytics_storage: "granted",
            ad_storage: "granted",
            ad_user_data: "granted",
            ad_personalization: "granted",
          },
        ]);
      }

      // Try to update Site Kit if available
      if (window.googlesitekit && consentData.categories.analytics) {
        try {
          window.googlesitekit.dispatch("modules/analytics-4").setConsentState({
            analytics_storage: "granted",
            // Only set ad_storage to granted if marketing consent was given
            ad_storage: consentData.categories.marketing ? "granted" : "denied",
            ad_user_data: consentData.categories.marketing
              ? "granted"
              : "denied",
            ad_personalization: consentData.categories.marketing
              ? "granted"
              : "denied",
          });
        } catch (e) {
          // Error handling for Site Kit update
          if (window.cookieConsentSettings?.debug) {
            console.error("Error updating Site Kit consent state:", e);
          }
        }
      }

      // Create a persistent banner notification instead of reloading
      const notification = document.createElement("div");
      notification.style.cssText =
        "position:fixed; bottom:0; left:0; right:0; background:#4C4CFF; color:white; padding:10px; text-align:center; z-index:99999;";
      notification.innerHTML =
        'Your cookie preferences have been saved. <button style="background:white; color:#4C4CFF; border:none; padding:5px 10px; margin-left:10px; cursor:pointer;">Reload page</button>';
      document.body.appendChild(notification);

      // Add reload button handler
      notification.querySelector("button").addEventListener("click", () => {
        window.location.reload();
      });
    }

    applyConsent() {
      const consent = this.getStoredConsent();
      if (!consent) {
        return;
      }

      // Configure HubSpot based on consent
      if (window.hubspot) {
        window.hubspot.setConsent({
          analytics: consent.categories.analytics,
          functionality: consent.categories.functional,
          marketing: consent.categories.marketing,
        });
      }

      // Prepare consent settings with proper granular consent
      // Each category should be mapped to the appropriate Google Consent Mode signal
      const consentSettings = {
        // Analytics is only granted if the user specifically accepts analytics
        analytics_storage: consent.categories.analytics ? "granted" : "denied",

        // Functional cookies control functionality and personalization
        functionality_storage: consent.categories.functional
          ? "granted"
          : "denied",
        personalization_storage: consent.categories.functional
          ? "granted"
          : "denied",

        // Security storage is always granted (necessary cookies)
        security_storage: "granted",

        // Marketing consent ONLY affects ad storage, user data, and personalization
        // These should never be granted if only analytics consent is given
        ad_storage: consent.categories.marketing ? "granted" : "denied",
        ad_user_data: consent.categories.marketing ? "granted" : "denied",
        ad_personalization: consent.categories.marketing ? "granted" : "denied",

        wait_for_update: 2000,
      };

      // Get region setting from cookieConsentSettings if available
      if (
        window.cookieConsentSettings &&
        window.cookieConsentSettings.consent_region
      ) {
        if (window.cookieConsentSettings.consent_region === "NO") {
          consentSettings.region = ["NO"];
        } else if (window.cookieConsentSettings.consent_region === "EEA") {
          // EEA countries list - all EU countries plus Norway, Iceland, and Liechtenstein
          consentSettings.region = [
            "AT",
            "BE",
            "BG",
            "HR",
            "CY",
            "CZ",
            "DK",
            "EE",
            "FI",
            "FR",
            "DE",
            "GR",
            "HU",
            "IE",
            "IT",
            "LV",
            "LT",
            "LU",
            "MT",
            "NL",
            "PL",
            "PT",
            "RO",
            "SK",
            "SI",
            "ES",
            "SE", // EU countries
            "NO",
            "IS",
            "LI",
            "GB", // Norway, Iceland, Liechtenstein, UK
          ];
        }
        // For 'GLOBAL', we don't set any region which applies restrictions globally
      } else {
        // Default to NO if not configured
        consentSettings.region = ["NO"];
      }

      // Push consent update to dataLayer
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push(["consent", "update", consentSettings]);

      // Log consent for debugging
      if (window.cookieConsentSettings?.debug) {
        console.log(
          "Applying consent settings to Google Consent Mode:",
          consentSettings
        );
        console.log("Based on user consent categories:", consent.categories);
      }

      // Only update ad settings if marketing consent was explicitly given
      if (consent.categories.marketing) {
        setTimeout(() => {
          const adSettings = {
            ad_storage: "granted",
            ad_user_data: "granted",
            ad_personalization: "granted",
          };

          // Apply the same region settings
          if (consentSettings.region) {
            adSettings.region = consentSettings.region;
          }

          window.dataLayer.push(["consent", "update", adSettings]);

          if (window.cookieConsentSettings?.debug) {
            console.log(
              "Applying additional marketing consent settings:",
              adSettings
            );
          }
        }, 500);
      }

      // Special handling for functional-only consent
      // This ensures that if the user only gave functional consent, we still update relevant categories
      if (
        consent.categories.functional &&
        !consent.categories.analytics &&
        !consent.categories.marketing
      ) {
        setTimeout(() => {
          const functionalSettings = {
            functionality_storage: "granted",
            personalization_storage: "granted",
          };

          // Apply the same region settings
          if (consentSettings.region) {
            functionalSettings.region = consentSettings.region;
          }

          window.dataLayer.push(["consent", "update", functionalSettings]);

          if (window.cookieConsentSettings?.debug) {
            console.log(
              "Applying additional functional-only consent settings:",
              functionalSettings
            );
          }
        }, 300);
      }
    }

    // New method to handle cookie setting
    setConsentCookie(consentData) {
      // IMPROVED DOMAIN HANDLING
      const hostname = window.location.hostname;
      // Check if hostname is an IP address
      const isIP = /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/.test(hostname);
      let cookieDomain;

      if (isIP) {
        // For IP addresses, don't set domain attribute at all
        cookieDomain = "";
      } else {
        // Extract domain parts
        const domainParts = hostname.split(".");

        // Handle different domain formats
        if (domainParts.length > 2) {
          // For subdomains (e.g., test.example.com), use root domain (example.com)
          cookieDomain = `.${domainParts.slice(-2).join(".")}`;
        } else {
          // For apex domains (e.g., example.com)
          cookieDomain = `.${hostname}`;
        }
      }

      // Encode the data
      const cookieValue = encodeURIComponent(JSON.stringify(consentData));

      // BUILD COOKIE STRING WITH ALL NECESSARY ATTRIBUTES
      const cookieAttributes = [];

      // Basic cookie with name and value
      cookieAttributes.push(`${this.storageKey}=${cookieValue}`);

      // Path - always set to root
      cookieAttributes.push("path=/");

      // Domain - only add if we have a valid domain (not IP)
      if (cookieDomain) {
        cookieAttributes.push(`domain=${cookieDomain}`);
      }

      // Expiration - 1 year
      cookieAttributes.push("max-age=31536000");

      // SameSite attribute - use Lax for better compatibility
      cookieAttributes.push("SameSite=Lax");

      // Secure flag for HTTPS connections
      if (window.location.protocol === "https:") {
        cookieAttributes.push("Secure");
      }

      // Join all attributes
      const cookieString = cookieAttributes.join("; ");

      // Set the cookie
      document.cookie = cookieString;

      // Store in localStorage as backup
      try {
        localStorage.setItem(this.storageKey, JSON.stringify(consentData));
      } catch (e) {
        // Error handling for localStorage saving
      }
    }

    /**
     * Helper method for debug logging
     * @param {string} message - The message to log
     * @param {any} data - Optional data to log
     */
    debugLog(message, data = null) {
      if (
        typeof window.customCookieConsent?.debug !== "undefined" &&
        window.customCookieConsent.debug === true
      ) {
        if (data !== null) {
          console.log(`[Cookie Consent] ${message}`, data);
        } else {
          console.log(`[Cookie Consent] ${message}`);
        }
      }
    }

    /**
     * Dispatches a consent update event with the consent data
     * @param {Object} consentData - The consent data to include
     */
    dispatchConsentUpdate(consentData) {
      // Create an object with just the category values for the event detail
      const categories = {};
      if (consentData && consentData.categories) {
        Object.keys(consentData.categories).forEach((category) => {
          categories[category] = !!consentData.categories[category]; // Convert to boolean
        });
      }

      // Create and dispatch the event
      const event = new CustomEvent("consentUpdated", {
        detail: categories,
      });

      if (window.cookieConsentSettings?.debug) {
        console.log("Dispatching consent update event:", categories);
      }

      window.dispatchEvent(event);

      // Also trigger any refresh logic for consent data displays
      this.refreshConsentDataDisplays();
    }

    /**
     * Refreshes any consent data displays on the page
     */
    refreshConsentDataDisplays() {
      // Find refresh buttons in consent data displays
      const refreshButtons = document.querySelectorAll(
        "#cookie-consent-refresh"
      );
      if (refreshButtons.length > 0) {
        // Click each refresh button with a small delay
        setTimeout(() => {
          refreshButtons.forEach((button) => {
            try {
              button.click();
            } catch (e) {
              // Ignore errors with button clicking
            }
          });
        }, 300);
      }
    }
  };

  // Create a single instance if one doesn't already exist
  if (!window.consentManager) {
    try {
      // Use a try-catch to prevent errors if there are problems creating the instance
      window.consentManager = new window.ConsentManager();

      if (window.cookieConsentSettings?.debug) {
        console.log("[ConsentManager] Instance created successfully");
      }
    } catch (error) {
      console.error("[ConsentManager] Error creating instance:", error);
    }
  }
} else {
  // Class already defined, just log it in debug mode
  if (window.cookieConsentSettings?.debug) {
    console.log(
      "[ConsentManager] Class already defined, skipping redefinition"
    );
  }
}
