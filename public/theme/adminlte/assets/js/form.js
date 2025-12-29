function handleFormSubmission(formSelector) {
  const form = $(formSelector);
  const submitBtn = form.find('[type="submit"]');

  form.on("submit", function (e) {
    e.preventDefault();

    if (submitBtn.prop("disabled")) return;

    // Clear previous errors
    form.find(".is-invalid").removeClass("is-invalid");
    form.find(".invalid-feedback").remove();

    const originalText = submitBtn.html();

    if (submitBtn.data("loading-text") == "spinner") {
      submitBtn
        .prop("disabled", true)
        .html('<span class="spinner-border spinner-border-sm me-1"></span>');
    } else {
      submitBtn
        .prop("disabled", true)
        .html(
          '<span class="spinner-border spinner-border-sm me-1"></span> Please wait...'
        );
    }

    const formData = new FormData(this);

    // If there's an image upload box on this form, append only images currently in preview
    if (typeof window.imgArray !== "undefined" && window.imgArray.length > 0) {
      formData.delete("attachments[]");
      window.imgArray.forEach((file) => {
        formData.append("attachments[]", file);
      });
    }

    $.ajax({
      url: form.attr("action"),
      method: form.attr("method"),
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        Swal.fire({
          icon: "success",
          title: "Success!",
          iconColor: "#39767b",
          text: response.message || "Form submitted successfully.",
          timer: 2000,
          showConfirmButton: false,
        }).then(() => {
          if (response.redirect) {
            window.location.href = response.redirect;
          }
        });

        form[0].reset();
        // Clear previews and reset imgArray for this upload box
        $(".upload__img-wrap").empty();
        window.imgArray = [];
      },
      error: function (xhr) {
        handleValidationErrors(xhr, form);
      },
      complete: function () {
        submitBtn.prop("disabled", false).html(originalText);
      },
    });
  });
}
/**
 * Master error handler
 */
function handleValidationErrors(xhr, form) {
  switch (xhr.status) {
    case 422:
      return handleValidationError422(xhr, form);
    case 429:
      return handleTooManyRequests429(xhr);
    case 401:
      return handleUnauthorized401(xhr);
    case 403:
      return handleForbidden403(xhr);
    default:
      return handleServerError(xhr);
  }
}

/**
 * 422 – Validation errors
 */
function handleValidationError422(xhr, form) {
  if (!xhr.responseJSON?.errors) {
    return handleServerError(xhr);
  }

  const errors = xhr.responseJSON.errors;

  Swal.fire({
    icon: "error",
    title: "Validation Error",
    text: xhr.responseJSON.message || "Please fix the form errors below.",
    timer: 3000,
    confirmButtonColor: "#39767b",
  });

  $.each(errors, function (field, messages) {
    let input = form.find(`[name="${field}"]`);

    // Handle nested fields like "address.city"
    if (input.length === 0 && field.includes(".")) {
      const flatName = field.replace(/\./g, "\\.").replace(/\[\]/g, "");
      input = form.find(`[name="${flatName}"]`);
    }

    if (input.length) {
      input.addClass("is-invalid");

      // Find predefined error container if available
      const errorContainer = form.find(`#${field}-error`);

      if (errorContainer.length) {
        errorContainer.text(messages[0]).addClass("invalid-feedback").show();
      } else if (!input.next(".invalid-feedback").length) {
        input.after(`<div class="invalid-feedback">${messages[0]}</div>`);
      }

      // Remove error dynamically when user edits input
      input.off("input change").on("input change", function () {
        $(this).removeClass("is-invalid");
        if (errorContainer.length) {
          errorContainer.text("").hide();
        } else {
          $(this).next(".invalid-feedback").remove();
        }
      });
    }
  });
}

/**
 * 429 – Too many requests
 */
function handleTooManyRequests429(xhr) {
  const retryAfter = xhr.getResponseHeader("Retry-After") || 60;
  Swal.fire({
    icon: "warning",
    title: "Too Many Requests",
    text:
      xhr.responseJSON?.message ||
      `You’ve made too many requests. Please try again after ${retryAfter} seconds.`,
    confirmButtonColor: "#39767b",
  });
}

/**
 * 401 – Unauthorized
 */
function handleUnauthorized401(xhr) {
  Swal.fire({
    icon: "warning",
    title: "Unauthorized",
    text:
      xhr.responseJSON?.message ||
      "Your session has expired. Please log in again.",
    confirmButtonText: "Login",
    confirmButtonColor: "#39767b",
  }).then(() => {
    location.reload(); // or redirect to login if needed
  });
}

/**
 * 403 – Forbidden
 */
function handleForbidden403(xhr) {
  Swal.fire({
    icon: "error",
    title: "Access Denied",
    text:
      xhr.responseJSON?.message ||
      "You do not have permission to perform this action.",
    confirmButtonColor: "#39767b",
  });
}

/**
 * 500+ – Server or unexpected errors
 */
function handleServerError(xhr) {
  Swal.fire({
    icon: "error",
    title: "Server Error",
    text:
      xhr.responseJSON?.message ||
      "Something went wrong. Please try again later.",
    confirmButtonColor: "#39767b",
  });

  console.error("Server error:", xhr);
}

$(document).ready(function () {
  $("form.ajax-form").each(function () {
    handleFormSubmission(this);
  });
});

$(document).ready(function () {
  $(document).on("click", ".btn-delete", function () {
    const button = $(this);
    const url = button.data("url");
    const refresh = button.data("refresh");
    const removeObject = button.data("remove");

    Swal.fire({
      title: "Are you sure?",
      text: "This action cannot be undone!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Yes",
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: url,
          type: "POST",
          data: {
            _method: "DELETE",
            _token: $('meta[name="csrf-token"]').attr("content"),
          },
          success: function (response) {
            Swal.fire({
              icon: "success",
              title: response.title || "Deleted!",
              text: response.message || "Record deleted successfully.",
              timer: 2000,
              showConfirmButton: false,
            }).then(() => {
              if (refresh !== false) {
                if (response.redirect) {
                  window.location.href = response.redirect;
                } else {
                  location.reload();
                }
              }

              if (removeObject) {
                $(removeObject).remove();
              }
            });
          },
          error: function (xhr) {
            Swal.fire(
              "Error",
              "Something went wrong. Please try again.",
              "error"
            );
            console.error(xhr);
          },
        });
      }
    });
  });
});

function getAside() {
  event.preventDefault();

  const $button = $(event.currentTarget);
  const url = $button.data("url");

  if (url && url.length == 0) {
    return false;
  }

  $.ajax({
    url: url,
    method: "GET",
    success: function (res) {
      $("#aside-content").html(res.data.view);
      $("#open-aside-button").click();
    },
  });
}

function getFormFilters(formSelector, updateUrl = false) {
  let filters = {};
  let params = new URLSearchParams(window.location.search); // current query string

  // Ensure formSelector is a jQuery object
  if (!(formSelector instanceof jQuery)) {
    formSelector = $(formSelector);
  }

  // Collect all form field values
  $.each(formSelector.serializeArray(), (i, field) => {
    const name = field.name;
    const value = field.value?.trim();

    // Ignore empty fields
    if (!value) {
      params.delete(name); // remove from URL if previously present
      return;
    }

    // Build filter object (supporting multi-selects)
    if (filters[name]) {
      if (!Array.isArray(filters[name])) {
        filters[name] = [filters[name]];
      }
      filters[name].push(value);
    } else {
      filters[name] = value;
    }

    // Update params for URL
    params.set(name, value);
  });

  // Clean up query string if all filters are removed
  if ($.isEmptyObject(filters)) {
    if (updateUrl) {
      history.replaceState(null, "", window.location.pathname);
    }
    return {};
  }

  // If filters exist and updateUrl = true → update the URL
  if (updateUrl) {
    const newQuery = params.toString();
    const newUrl = newQuery
      ? `${window.location.pathname}?${newQuery}`
      : window.location.pathname;

    history.replaceState(null, "", newUrl);
  }

  return filters;
}

// function getFormFilters(formSelector, updateUrl = false) {
//   let filters = {};
//   const params = new URLSearchParams();

//   if (formSelector instanceof jQuery === false) {
//     formSelector = $(formSelector);
//   }

//   $.each(formSelector.serializeArray(), (i, field) => {
//     if (!field.value) return;

//     if (filters[field.name]) {
//       if (!Array.isArray(filters[field.name])) {
//         filters[field.name] = [filters[field.name]];
//       }
//       filters[field.name].push(field.value);
//     } else {
//       filters[field.name] = field.value;
//     }
//   });

//   if (!updateUrl) return filters;

//   const newUrl = `${window.location.pathname}?${params.toString()}`;

//   history.replaceState(null, "", newUrl);

//   return filters;
// }

$(".select2").select2({
  placeholder: "Select Option",
  width: "100%",
});
