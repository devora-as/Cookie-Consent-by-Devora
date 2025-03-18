/**
 * JavaScript for Open Cookie Database integration
 */
(function ($) {
  "use strict";

  // Wait for DOM to be ready
  $(function () {
    // Handle the "Update Now" button click
    $(".js-force-ocd-update").on("click", function (e) {
      e.preventDefault();
      const $button = $(this);

      // Disable the button and show loading state
      $button.prop("disabled", true).text(cookieConsentAdmin.updating_text);

      // Make AJAX request to update the database
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "custom_cookie_force_ocd_update",
          nonce: cookieConsentAdmin.nonce,
        },
        success: function (response) {
          if (response.success) {
            // Show success message
            $('<p class="notice notice-success">')
              .text(response.data.message)
              .insertAfter($button);

            // Update the button text
            $button.text(cookieConsentAdmin.update_complete_text);

            // Reload the page after 2 seconds to show updated information
            setTimeout(function () {
              window.location.reload();
            }, 2000);
          } else {
            // Show error message
            $('<p class="notice notice-error">')
              .text(response.data.message)
              .insertAfter($button);

            // Re-enable the button
            $button
              .prop("disabled", false)
              .text(cookieConsentAdmin.update_now_text);
          }
        },
        error: function () {
          // Show generic error message
          $('<p class="notice notice-error">')
            .text(cookieConsentAdmin.ajax_error_text)
            .insertAfter($button);

          // Re-enable the button
          $button
            .prop("disabled", false)
            .text(cookieConsentAdmin.update_now_text);
        },
      });
    });
  });
})(jQuery);
