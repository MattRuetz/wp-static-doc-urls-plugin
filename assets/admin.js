jQuery(document).ready(function ($) {
  // Handle form submission
  $("#sud-mapping-form").on("submit", function (e) {
    e.preventDefault();

    var formData = {
      action: "sud_save_mapping",
      nonce: sud_ajax.nonce,
      static_slug: $("#static_slug").val(),
      document_title: $("#document_title").val(),
      document_url: $("#document_url").val(),
      mapping_id: $("#mapping_id").val(),
    };

    var $submitBtn = $("#save-mapping");
    var originalText = $submitBtn.val();
    $submitBtn.val("Saving...").prop("disabled", true);

    $.ajax({
      url: sud_ajax.ajax_url,
      type: "POST",
      data: formData,
      success: function (response) {
        if (response.success) {
          // Reset form
          $("#sud-mapping-form")[0].reset();
          $("#mapping_id").val("0");
          $("#save-mapping").val("Save Mapping");
          $("#cancel-edit").hide();

          // Show success message
          showNotice("success", response.data);

          // Reload the page to show updated list
          setTimeout(function () {
            location.reload();
          }, 1000);
        } else {
          showNotice("error", response.data);
        }
      },
      error: function () {
        showNotice("error", "An error occurred while saving the mapping.");
      },
      complete: function () {
        $submitBtn.val(originalText).prop("disabled", false);
      },
    });
  });

  // Handle edit button click
  $(".edit-mapping").on("click", function () {
    var $row = $(this).closest("tr");
    var mappingId = $(this).data("id");

    // Get data from the table row
    var staticSlug = $row.find("code").text().split("/docs/")[1];
    var documentTitle = $row.find("td:nth-child(2)").text();
    var documentUrl = $row.find("td:nth-child(3) a").attr("href");

    // Populate form
    $("#static_slug").val(staticSlug);
    $("#document_title").val(documentTitle);
    $("#document_url").val(documentUrl);
    $("#mapping_id").val(mappingId);

    // Update form UI
    $("#save-mapping").val("Update Mapping");
    $("#cancel-edit").show();

    // Scroll to form
    $("html, body").animate(
      {
        scrollTop: $("#sud-mapping-form").offset().top - 100,
      },
      500
    );

    // Focus on first field
    $("#static_slug").focus();
  });

  // Handle cancel edit button
  $("#cancel-edit").on("click", function () {
    // Reset form
    $("#sud-mapping-form")[0].reset();
    $("#mapping_id").val("0");
    $("#save-mapping").val("Save Mapping");
    $(this).hide();
  });

  // Handle delete button click
  $(".delete-mapping").on("click", function () {
    var mappingId = $(this).data("id");
    var $row = $(this).closest("tr");
    var documentTitle = $row.find("td:nth-child(2)").text();

    if (
      confirm(
        'Are you sure you want to delete the mapping for "' +
          documentTitle +
          '"? This cannot be undone.'
      )
    ) {
      var $deleteBtn = $(this);
      var originalText = $deleteBtn.text();
      $deleteBtn.text("Deleting...").prop("disabled", true);

      $.ajax({
        url: sud_ajax.ajax_url,
        type: "POST",
        data: {
          action: "sud_delete_mapping",
          nonce: sud_ajax.nonce,
          mapping_id: mappingId,
        },
        success: function (response) {
          if (response.success) {
            // Remove the row with animation
            $row.fadeOut(400, function () {
              $(this).remove();

              // Check if table is empty
              if ($(".wp-list-table tbody tr").length === 0) {
                location.reload();
              }
            });
            showNotice("success", response.data);
          } else {
            showNotice("error", response.data);
            $deleteBtn.text(originalText).prop("disabled", false);
          }
        },
        error: function () {
          showNotice("error", "An error occurred while deleting the mapping.");
          $deleteBtn.text(originalText).prop("disabled", false);
        },
      });
    }
  });

  // Handle browse media button
  $("#browse-media").on("click", function (e) {
    e.preventDefault();

    var mediaUploader = wp.media({
      title: "Select Document",
      library: {
        type: [
          "application/pdf",
          "application/msword",
          "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
          "text/plain",
          "image",
        ],
      },
      button: {
        text: "Select Document",
      },
      multiple: false,
    });

    mediaUploader.on("select", function () {
      var attachment = mediaUploader.state().get("selection").first().toJSON();
      $("#document_url").val(attachment.url);

      // Auto-populate title if empty
      if (!$("#document_title").val()) {
        $("#document_title").val(attachment.title);
      }
    });

    mediaUploader.open();
  });

  // Validate static slug input
  $("#static_slug").on("input", function () {
    var slug = $(this).val();
    var sanitizedSlug = slug
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, "") // Remove invalid characters
      .replace(/\s+/g, "-") // Replace spaces with hyphens
      .replace(/-{2,}/g, "-"); // Replace multiple consecutive hyphens with single

    // Only auto-correct if there are actual invalid characters, not just for formatting
    if (slug !== sanitizedSlug) {
      $(this).val(sanitizedSlug);
    }

    // Update preview URL (use the actual current value for live preview)
    var previewUrl = $(this).closest("td").find(".description code");
    var currentValue = $(this).val();
    if (currentValue) {
      previewUrl.text(window.location.origin + "/docs/" + currentValue);
    } else {
      previewUrl.text(window.location.origin + "/docs/your-slug");
    }
  });

  // Clean up leading/trailing hyphens only when user finishes typing
  $("#static_slug").on("blur", function () {
    var slug = $(this).val();
    var cleanedSlug = slug.replace(/^-+|-+$/g, ""); // Remove leading/trailing hyphens only on blur

    if (slug !== cleanedSlug) {
      $(this).val(cleanedSlug);
      // Update preview URL after cleaning
      var previewUrl = $(this).closest("td").find(".description code");
      if (cleanedSlug) {
        previewUrl.text(window.location.origin + "/docs/" + cleanedSlug);
      } else {
        previewUrl.text(window.location.origin + "/docs/your-slug");
      }
    }
  });

  // Show notice function
  function showNotice(type, message) {
    var noticeClass = type === "success" ? "notice-success" : "notice-error";
    var notice = $(
      '<div class="notice ' +
        noticeClass +
        ' is-dismissible"><p>' +
        message +
        "</p></div>"
    );

    $(".wrap h1").after(notice);

    // Auto-dismiss after 5 seconds
    setTimeout(function () {
      notice.fadeOut();
    }, 5000);

    // Handle manual dismiss
    notice.on("click", ".notice-dismiss", function () {
      notice.fadeOut();
    });
  }
});
