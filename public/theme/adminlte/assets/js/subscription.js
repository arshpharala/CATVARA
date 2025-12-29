/**
 * Checkout Wizard (URL-Driven + Auto-Restore + Scroll)
 * Clean Optimized Version — Arshdeep Singh
 */
document.addEventListener("DOMContentLoaded", function () {
  // ==============================
  // State
  // ==============================
  const selected = {
    customer_id: typeof CUSTOMER_ID !== "undefined" ? CUSTOMER_ID : null,
    meal_id: null,
    package_id: null,
    meal_package_price_id: null,
  };

  const baseUrl = window.location.origin + window.location.pathname;

  // ==============================
  // Setup
  // ==============================
  $.ajaxSetup({
    headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
  });

  // ==============================
  // Helpers
  // ==============================
  const loader = (msg) =>
    `<div class="text-center py-4 text-muted"><div class="spinner-border text-primary mb-2"></div>${msg}</div>`;

  const errorBox = (msg) =>
    `<div class="alert alert-danger text-center m-3"><i class="fas fa-exclamation-triangle me-2"></i>${msg}</div>`;

  const scrollToCard = ($el) =>
    $el &&
    $el.length &&
    $("html, body").animate({ scrollTop: $el.offset().top - 150 }, 300);

  const updateUrl = () => {
    const params = new URLSearchParams();
    if (selected.meal_id) params.set("meal_id", selected.meal_id);
    if (selected.package_id) params.set("package_id", selected.package_id);
    if (selected.meal_package_price_id)
      params.set("price_id", selected.meal_package_price_id);
    const newUrl = params.toString()
      ? `${baseUrl}?${params.toString()}`
      : baseUrl;
    history.replaceState({}, "", newUrl);
  };

  const clearParams = (...keys) => {
    const u = new URL(location.href);
    keys.forEach((k) => u.searchParams.delete(k));
    history.replaceState({}, "", u.pathname + u.search);
  };

  const showStep = (step) => {
    $("#wizardContent section").addClass("d-none");
    $(step).removeClass("d-none").hide().fadeIn(200);
    $("#wizardSteps li").removeClass("fw-bold text-primary");
    const key = step.replace("#step-", "");
    $(`#wizardSteps li[data-step='${key}']`).addClass("fw-bold text-primary");
    $("html, body").animate({ scrollTop: 0 }, 200);
  };

  // ==============================
  // Generic AJAX Loader
  // ==============================
  const loadAjax = (url, container, step, afterLoad) => {
    $(container).html(loader("Loading..."));
    $.get(url)
      .done((res) => {
        if (res.success) {
          $(container).html(res.html);
          showStep(step);
          afterLoad && afterLoad();
        } else $(container).html(errorBox("Failed to load data"));
      })
      .fail(() => $(container).html(errorBox("Server Error")));
  };

  // ==============================
  // Step Loaders
  // ==============================
  const loadPackages = () =>
    loadAjax(
      `/admin/ajax/meal/${selected.meal_id}/packages`,
      "#packages-content",
      "#step-package",
      () => {
        if (selected.package_id) {
          const $pkg = $(
            `.pkg-card [data-id='${selected.package_id}']`
          ).closest(".pkg-card");
          $pkg.addClass("active");
          scrollToCard($pkg);
        }
      }
    );

  const loadPrices = () =>
    loadAjax(
      `/admin/ajax/meal/${selected.meal_id}/package/${selected.package_id}/prices`,
      "#priceGrid",
      "#step-price",
      () => {
        if (selected.meal_package_price_id) {
          const $price = $(
            `.price-card[data-id='${selected.meal_package_price_id}']`
          );
          $price.addClass("active");
          scrollToCard($price);
        }
      }
    );

  const loadSummary = () =>
    $.get(
      `/admin/ajax/customer/${selected.customer_id}/meal-package-price/${selected.meal_package_price_id}/summary`,
      (res) => {
        if (res.success) {
          $("#review-content").html(res.html);
          showStep("#step-summary");
        } else $("#review-content").html(errorBox("Failed to load review"));
      }
    );

  // ==============================
  // Step Events
  // ==============================
  $(document).on("click", ".selectMeal", function () {
    const $card = $(this).closest(".meal-card");
    $(".meal-card").removeClass("active");
    $card.addClass("active");
    selected.meal_id = $(this).data("meal-id");
    selected.package_id = selected.meal_package_price_id = null;
    updateUrl();
    loadPackages();
    scrollToCard($card);
  });

  $(document).on("click", ".selectPackage", function () {
    const $card = $(this).closest(".pkg-card");
    $(".pkg-card").removeClass("active");
    $card.addClass("active");
    selected.package_id = $(this).data("id");
    selected.meal_package_price_id = null;
    updateUrl();
    loadPrices();
    scrollToCard($card);
  });

  $(document).on("click", ".price-card", function () {
    const $card = $(this);
    $(".price-card").removeClass("active");
    $card.addClass("active");
    selected.meal_package_price_id = $card.data("id");
    updateUrl();
    loadSummary();
    scrollToCard($card);
  });

  // ==============================
  // Back Navigation
  // ==============================
  $("#backToMeal").on("click", () => {
    Object.assign(selected, {
      meal_id: null,
      package_id: null,
      meal_package_price_id: null,
    });
    clearParams("meal_id", "package_id", "price_id");
    updateUrl();
    showStep("#step-meal");
  });

  $("#backToPackage").on("click", () => {
    selected.package_id = selected.meal_package_price_id = null;
    clearParams("package_id", "price_id");
    updateUrl();
    loadPackages();
  });

  $("#backToPrice").on("click", () => {
    selected.meal_package_price_id = null;
    clearParams("price_id");
    updateUrl();
    loadPrices();
  });

  // ==============================
  // Init from URL (Auto Restore)
  // ==============================
  (function init() {
    const q = new URLSearchParams(location.search);
    selected.meal_id = q.get("meal_id");
    selected.package_id = q.get("package_id");
    selected.meal_package_price_id = q.get("price_id");

    if (selected.meal_id) {
      const $meal = $(
        `.meal-card [data-meal-id='${selected.meal_id}']`
      ).closest(".meal-card");
      $meal.addClass("active");
      scrollToCard($meal);
    }

    if (
      selected.meal_id &&
      !selected.package_id &&
      !selected.meal_package_price_id
    )
      loadPackages();
    else if (
      selected.meal_id &&
      selected.package_id &&
      !selected.meal_package_price_id
    )
      loadPrices();
    else if (
      selected.meal_id &&
      selected.package_id &&
      selected.meal_package_price_id
    )
      loadSummary();
    else showStep("#step-meal");
  })();
});
