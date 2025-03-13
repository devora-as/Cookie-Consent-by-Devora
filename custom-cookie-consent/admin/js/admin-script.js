/**
 * Custom Cookie Consent Admin JavaScript
 * Handles the admin interface functionalities
 */

(function ($) {
  "use strict";

  // Cookie Management Dashboard
  const CookieAdmin = {
    init: function () {
      this.bindEvents();
      this.initUI();
    },

    bindEvents: function () {
      // Scanner page - Scan button
      $(".js-cookie-scan-button").on("click", this.handleScan);

      // Cookie categorization
      $(".js-cookie-categorize").on("change", this.handleCategorize);
      $(".js-cookie-save-category").on("click", this.handleSaveCategory);

      // Bulk categorization
      $(".js-cookie-bulk-categorize").on("click", this.handleBulkCategorize);

      // Settings page - Save settings for all forms
      $(
        ".js-cookie-settings-form, .js-integration-settings-form, .js-scanner-settings-form"
      ).on("submit", this.handleSaveSettings);
    },

    initUI: function () {
      // Initialize any UI components
      this.updateStats();
    },

    updateStats: function () {
      // Update statistics on the dashboard
      const stats = this.getCookieStats();
      $(".js-total-cookies").text(stats.total);
      $(".js-categorized-cookies").text(stats.categorized);
      $(".js-uncategorized-cookies").text(stats.uncategorized);
    },

    getCookieStats: function () {
      // Calculate cookie stats from table
      const total = $(".cookie-list-table tbody tr").length;
      const categorized = $(".cookie-status-categorized").length;
      const uncategorized = total - categorized;

      return {
        total: total,
        categorized: categorized,
        uncategorized: uncategorized,
      };
    },

    handleScan: function (e) {
      e.preventDefault();

      const $button = $(this);
      $button.prop("disabled", true).text("Scanning...");

      // AJAX request to run cookie scan
      $.ajax({
        url: customCookieAdminSettings.ajaxUrl,
        type: "POST",
        data: {
          action: "custom_cookie_scan_now",
          _nonce: customCookieAdminSettings.scanNonce,
        },
        success: function (response) {
          if (response.success) {
            CookieAdmin.showNotice(
              "success",
              customCookieAdminSettings.messages.scanComplete
            );
            // Reload the page after a delay to show updated results
            setTimeout(function () {
              window.location.reload();
            }, 1500);
          } else {
            // Fix for handling error objects properly
            let errorMessage = "Error scanning cookies";

            if (response.data) {
              if (typeof response.data === "object" && response.data.message) {
                errorMessage = response.data.message;
              } else if (typeof response.data === "string") {
                errorMessage = response.data;
              }
            }

            CookieAdmin.showNotice("error", errorMessage);
            $button.prop("disabled", false).text("Scan for Cookies");
          }
        },
        error: function (xhr, status, error) {
          let errorMessage = "Server error while scanning cookies";

          // Attempt to parse response if possible
          try {
            const response = JSON.parse(xhr.responseText);
            if (response && response.data && response.data.message) {
              errorMessage = response.data.message;
            }
          } catch (e) {
            // Use default error message if parsing fails
            console.error("Error parsing scan response:", e);
          }

          CookieAdmin.showNotice("error", errorMessage);
          $button.prop("disabled", false).text("Scan for Cookies");
        },
      });
    },

    handleCategorize: function () {
      const $select = $(this);
      const $row = $select.closest("tr");

      // Update action buttons
      $row.find(".js-cookie-save-category").show();
    },

    handleSaveCategory: function () {
      const $button = $(this);
      const $row = $button.closest("tr");
      const cookieName = $row.data("cookie-name");
      const category = $row.find(".js-cookie-categorize").val();

      $button.prop("disabled", true).text("Saving...");

      // AJAX request to categorize cookie
      $.ajax({
        url: customCookieAdminSettings.ajaxUrl,
        type: "POST",
        data: {
          action: "categorize_cookie",
          cookie_name: cookieName,
          category: category,
          nonce: customCookieAdminSettings.nonce,
        },
        success: function (response) {
          if (response.success) {
            // Update UI
            $row
              .find(".cookie-status")
              .removeClass("cookie-status-uncategorized")
              .addClass("cookie-status-categorized")
              .text("Categorized");

            CookieAdmin.showNotice(
              "success",
              customCookieAdminSettings.messages.cookieCategorized
            );
            $button.hide();

            // Update stats
            CookieAdmin.updateStats();
          } else {
            CookieAdmin.showNotice(
              "error",
              response.data || "Error categorizing cookie"
            );
          }
          $button.prop("disabled", false).text("Save");
        },
        error: function () {
          CookieAdmin.showNotice(
            "error",
            "Server error while categorizing cookie"
          );
          $button.prop("disabled", false).text("Save");
        },
      });
    },

    handleBulkCategorize: function () {
      const $button = $(this);
      const category = $(".js-bulk-category-select").val();

      if (!category) {
        CookieAdmin.showNotice("error", "Please select a category");
        return;
      }

      $button.prop("disabled", true).text("Processing...");

      // Collect selected cookies
      const selectedCookies = [];
      $(".js-cookie-checkbox:checked").each(function () {
        const $row = $(this).closest("tr");
        selectedCookies.push({
          name: $row.data("cookie-name"),
          category: category,
        });
      });

      if (selectedCookies.length === 0) {
        CookieAdmin.showNotice("error", "No cookies selected");
        $button.prop("disabled", false).text("Apply");
        return;
      }

      // AJAX request to bulk categorize cookies
      $.ajax({
        url: customCookieAdminSettings.ajaxUrl,
        type: "POST",
        data: {
          action: "bulk_categorize_cookies",
          cookies: JSON.stringify(selectedCookies),
          nonce: customCookieAdminSettings.nonce,
        },
        success: function (response) {
          if (response.success) {
            CookieAdmin.showNotice(
              "success",
              customCookieAdminSettings.messages.bulkCategorized
            );
            // Reload the page after a delay to show updated results
            setTimeout(function () {
              window.location.reload();
            }, 1500);
          } else {
            CookieAdmin.showNotice(
              "error",
              response.data || "Error categorizing cookies"
            );
            $button.prop("disabled", false).text("Apply");
          }
        },
        error: function () {
          CookieAdmin.showNotice(
            "error",
            "Server error while categorizing cookies"
          );
          $button.prop("disabled", false).text("Apply");
        },
      });
    },

    handleSaveSettings: function (e) {
      e.preventDefault();

      const $form = $(this);
      const $submitButton = $form.find('[type="submit"]');

      // Determine which settings are being saved for the success message
      let successMessage = "Settings saved successfully";
      let formAction = "save_cookie_settings"; // Default action

      // Always use the same nonce for all settings forms
      let nonceValue = customCookieAdminSettings.nonce;

      // Debug logging and form type identification
      if ($form.hasClass("js-integration-settings-form")) {
        debugLog("Saving integration settings");
        successMessage =
          customCookieAdminSettings.messages.integrationSaved ||
          "Integration settings saved successfully";
        formAction = "save_integration_settings"; // Use a specific action for integration settings
      } else if ($form.hasClass("js-scanner-settings-form")) {
        debugLog("Saving scanner settings");
        successMessage =
          customCookieAdminSettings.messages.scannerSaved ||
          "Scanner settings saved successfully";
      } else if ($form.hasClass("js-cookie-settings-form")) {
        debugLog("Saving banner settings");
        successMessage =
          customCookieAdminSettings.messages.settingsSaved ||
          "Banner settings saved successfully";

        // Special handling for banner position
        const position = $form.find('select[name="position"]').val();
        debugLog("Banner position selected:", position);
      }

      $submitButton.prop("disabled", true).val("Saving...");

      // Create a new FormData object for better form handling
      const formData = new FormData($form[0]);

      // Add the action and nonce
      formData.append("action", formAction);
      formData.append("nonce", nonceValue);

      // Special handling for integration settings form - ensure checkboxes are included
      if ($form.hasClass("js-integration-settings-form")) {
        // Log checkbox states for debugging
        const wpConsentChecked = $form
          .find('input[name="wp_consent_api"]')
          .is(":checked");
        const siteKitChecked = $form
          .find('input[name="sitekit_integration"]')
          .is(":checked");
        const hubspotChecked = $form
          .find('input[name="hubspot_integration"]')
          .is(":checked");

        debugLog("Checkbox states before submission:", {
          wp_consent_api: wpConsentChecked,
          sitekit_integration: siteKitChecked,
          hubspot_integration: hubspotChecked,
        });

        // Ensure checkbox values are set properly (true/false values)
        formData.set("wp_consent_api", wpConsentChecked ? "1" : "0");
        formData.set("sitekit_integration", siteKitChecked ? "1" : "0");
        formData.set("hubspot_integration", hubspotChecked ? "1" : "0");
      }

      // Special handling for banner settings form - ensure position is included
      if ($form.hasClass("js-cookie-settings-form")) {
        const position = $form.find('select[name="position"]').val();
        if (position) {
          debugLog("Setting banner position to:", position);
          formData.set("position", position);
        }
      }

      // Log form data for debugging
      if (
        typeof customCookieAdminSettings !== "undefined" &&
        customCookieAdminSettings.debug === true
      ) {
        debugLog("Form action:", formAction);
        debugLog("Form nonce:", nonceValue);
        debugLog("Form data entries:");
        formData.forEach((value, key) => {
          debugLog(`${key}: ${value}`);
        });
      }

      // AJAX request to save settings
      $.ajax({
        url: customCookieAdminSettings.ajaxUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            CookieAdmin.showNotice("success", successMessage);

            // If we're changing the banner position, update the preview if available
            if (
              $form.hasClass("js-cookie-settings-form") &&
              $form.find('select[name="position"]').length
            ) {
              debugLog(
                "Banner position updated, refreshing preview if available"
              );
            }
          } else {
            const message =
              response.data && response.data.message
                ? response.data.message
                : "Error saving settings";

            CookieAdmin.showNotice("error", message);

            // Log the error for debugging
            debugLog("AJAX response error:", response);
          }

          $submitButton.prop("disabled", false).val("Save Settings");
        },
        error: function (xhr, status, error) {
          console.error("AJAX error:", status, error);
          debugLog("AJAX error details:", {
            status: status,
            error: error,
            responseText: xhr.responseText,
          });

          CookieAdmin.showNotice(
            "error",
            "Server error while saving settings. Please try again."
          );
          $submitButton.prop("disabled", false).val("Save Settings");
        },
      });
    },

    showNotice: function (type, message) {
      // Remove any existing notices
      $(".cookie-consent-notice").remove();

      // Create notice
      const $notice = $(
        '<div class="cookie-consent-notice cookie-consent-notice-' +
          type +
          '">' +
          message +
          "</div>"
      );

      // Append to notice container or create one
      if ($(".cookie-notice-container").length) {
        $(".cookie-notice-container").append($notice);
      } else {
        $(".cookie-consent-admin-header").after(
          $('<div class="cookie-notice-container"></div>').append($notice)
        );
      }

      // Auto hide after 5 seconds
      setTimeout(function () {
        $notice.fadeOut(300, function () {
          $(this).remove();
        });
      }, 5000);
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    CookieAdmin.init();
  });

  // Helper function for debug logging
  function debugLog(...args) {
    if (
      typeof customCookieAdminSettings !== "undefined" &&
      customCookieAdminSettings.debug === true
    ) {
      console.log(...args);
    }
  }
})(jQuery);
