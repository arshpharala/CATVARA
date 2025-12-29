/**
 * Checkout Wizard (Customer First Flow + Address Create)
 * Author: Arshdeep Singh
 */

document.addEventListener("DOMContentLoaded", function () {
  let selected = {
    customer: null,
    address: null,
    meal: null,
    pkg: null,
    price: null,
  };

  // ==============================
  // Setup
  // ==============================
  $.ajaxSetup({
    headers: {
      "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
    },
  });

  function showStep(step) {
    $("#wizardContent section").addClass("d-none");
    $(step).removeClass("d-none").hide().fadeIn(250);

    $("#wizardSteps li").removeClass("fw-bold text-primary");
    const key = step.replace("#step-", "");
    $(`#wizardSteps li[data-step='${key}']`).addClass("fw-bold text-primary");
  }

  function loader(msg) {
    return `<div class="text-center py-4 text-muted">
      <div class="spinner-border text-primary mb-2"></div>${msg}
    </div>`;
  }

  function errorBox(msg) {
    return `<div class="alert alert-danger text-center m-3">
      <i class="fas fa-exclamation-triangle me-2"></i>${msg}
    </div>`;
  }

  // ==============================
  // STEP 1: CUSTOMER SELECTION / CREATION
  // ==============================
  $("#btnCheckCustomer").on("click", function () {
    const email = $("input[name=customer_email]").val().trim();
    if (!email) return alert("Please enter a valid email.");

    $("#btnCheckCustomer")
      .prop("disabled", true)
      .html('<i class="fas fa-spinner fa-spin"></i> Checking...');

    $.get(`/admin/ajax/customers/find`, { email }, function (res) {
      $("#btnCheckCustomer")
        .prop("disabled", false)
        .html('<i class="fas fa-search me-1"></i> Check Customer');

      if (res.found) {
        selected.customer = res.customer;
        $("#customerInfoSection").addClass("d-none");
        $("#existingCustomerSection").removeClass("d-none");
        loadAddresses(res.customer.id);
      } else {
        selected.customer = null;
        $("#existingCustomerSection").addClass("d-none");
        $("#customerInfoSection").removeClass("d-none");
      }
    }).fail(() => {
      $("#btnCheckCustomer")
        .prop("disabled", false)
        .html('<i class="fas fa-search me-1"></i> Check Customer');
      alert("Server error while checking customer.");
    });
  });

  // Load existing addresses for a user
  function loadAddresses(userId) {
    $("#customerAddresses").html(loader("Loading saved addresses..."));
    $.get(`/admin/ajax/customers/${userId}/addresses`, function (res) {
      if (res.success && res.addresses.length) {
        renderAddresses(res.addresses);
      } else {
        $("#customerAddresses").html(`
          <div class="alert alert-warning text-center">No saved addresses found.</div>
          <div class="text-center mt-3">
            <button class="btn btn-outline-primary btn-sm" id="btnAddNewAddress">
              <i class="fas fa-plus me-1"></i> Add New Address
            </button>
          </div>
        `);
      }
    }).fail(() => {
      $("#customerAddresses").html(errorBox("Error loading addresses."));
    });
  }

  // Render address cards
  function renderAddresses(addresses) {
    let html = '<div class="row g-2">';
    addresses.forEach((addr) => {
      html += `<div class="col-md-6">
        <div class="card address-card p-3 shadow-sm" data-id="${addr.id}">
          <b>${addr.address}</b><br>
          <small class="text-muted">${addr.city?.name || ""}, ${
        addr.province?.name || ""
      }</small>
        </div>
      </div>`;
    });
    html += `
      <div class="col-md-6 d-flex align-items-center justify-content-center">
        <button class="btn btn-outline-primary btn-sm" id="btnAddNewAddress">
          <i class="fas fa-plus me-1"></i> Add New Address
        </button>
      </div>
    `;
    $("#customerAddresses").html(html);
  }

  // Select an address
  $(document).on("click", ".address-card", function () {
    $(".address-card").removeClass("active border-primary shadow-lg");
    $(this).addClass("active border-primary shadow-lg");
    selected.address = { id: $(this).data("id") };
    console.log("🏠 Selected Address:", selected.address);
  });

  // Add new address form dynamically
  $(document).on("click", "#btnAddNewAddress", function (e) {
    e.preventDefault();
    if ($("#newAddressForm").length) return; // already open

    $("#customerAddresses").after(`
      <div id="newAddressForm" class="border rounded p-3 bg-light mt-3">
        <h6 class="fw-bold mb-3">Add New Address</h6>
        <form id="createAddressForm">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Province</label>
              <select name="province_id" class="form-select" required>${window.provincesHTML}</select>
            </div>
            <div class="col-md-4">
              <label class="form-label">City</label>
              <input type="text" name="city" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Address</label>
              <input type="text" name="address" class="form-control" required>
            </div>
          </div>
          <div class="mt-3 text-end">
            <button type="submit" class="btn btn-success btn-sm">
              <i class="fas fa-save me-1"></i> Save Address
            </button>
          </div>
        </form>
      </div>
    `);
  });

  // Handle new address save
  $(document).on("submit", "#createAddressForm", function (e) {
    e.preventDefault();
    const form = $(this);
    form
      .find("button")
      .prop("disabled", true)
      .html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    $.ajax({
      url: `/admin/ajax/customers/${selected.customer.id}/addresses`,
      method: "POST",
      data: form.serialize(),
      success(res) {
        if (res.success) {
          $("#newAddressForm").remove();
          loadAddresses(selected.customer.id);
          selected.address = { id: res.address.id };
          Swal.fire("Saved!", "New address added successfully.", "success");
        } else {
          Swal.fire("Error", "Failed to add address.", "error");
        }
      },
      error() {
        Swal.fire("Error", "Server error while adding address.", "error");
      },
      complete() {
        form
          .find("button")
          .prop("disabled", false)
          .html('<i class="fas fa-save me-1"></i> Save Address');
      },
    });
  });

  // Proceed to meal step
  $("#toMeal").on("click", function (e) {
    e.preventDefault();

    // Ensure customer selected
    if (!selected.customer) {
      const name = $("input[name=customer_name]").val();
      const phone = $("input[name=customer_phone]").val();
      const email = $("input[name=customer_email]").val();
      if (!name || !phone || !email)
        return Swal.fire(
          "Missing info",
          "Please fill in customer details.",
          "warning"
        );
      selected.customer = { name, email, phone };
    }

    console.log("✅ Customer:", selected.customer);
    showStep("#step-meal");
  });

  // ==============================
  // STEP 2: MEAL
  // ==============================
  $(document).on("click", ".selectMeal", function () {
    const card = $(this).closest(".meal-card");
    selected.meal = {
      id: card.data("id"),
      name: card.data("name"),
      image: card.find("img").attr("src"),
    };
    $(".meal-card").removeClass("active");
    card.addClass("active");

    $("#packageGrid").html(loader("Loading packages..."));
    $.get(`/admin/ajax/checkout/packages/${selected.meal.id}`, function (res) {
      if (res.success) {
        $("#packageGrid").html(res.html);
        $("#selectedMealDisplay")
          .removeClass("d-none")
          .html(`<b>Selected Meal:</b> ${selected.meal.name}`);
        showStep("#step-package");
      } else $("#packageGrid").html(errorBox("Failed to load packages"));
    });
  });

  // ==============================
  // STEP 3: PACKAGE
  // ==============================
  $(document).on("click", ".selectPackage", function () {
    const card = $(this).closest(".pkg-card");
    selected.pkg = {
      id: card.data("id"),
      name: card.data("name"),
      image: card.find("img").attr("src"),
    };

    $(".pkg-card").removeClass("active");
    card.addClass("active");

    $("#priceGrid").html(loader("Loading prices..."));
    $.get(`/admin/ajax/checkout/prices/${selected.pkg.id}`, function (res) {
      if (res.success) {
        $("#priceGrid").html(res.html);
        $("#selectedPackageDisplay")
          .removeClass("d-none")
          .html(`<b>Selected Package:</b> ${selected.pkg.name}`);
        showStep("#step-price");
      } else $("#priceGrid").html(errorBox("Failed to load prices"));
    });
  });

  // ==============================
  // STEP 4: PRICE
  // ==============================
  $(document).on("click", ".price-card", function () {
    $(".price-card").removeClass("active");
    const card = $(this);
    card.addClass("active");

    selected.price = {
      id: card.data("id"),
      price: card.data("price"),
      duration: card.data("duration"),
      calorie: card.data("calorie"),
    };

    $("#selected-meal-package-price-id").val(selected.price.id);

    buildSummaryPreview();
    showStep("#step-summary");
  });

  // ==============================
  // STEP 5: SUMMARY
  // ==============================
  function buildSummaryPreview() {
    const summaryHTML = `
      <div class="p-4 bg-white rounded shadow-sm">
        <h5 class="fw-bold text-primary mb-3">Review Summary</h5>
        <div class="row">
          <div class="col-md-6">
            <p><b>Meal:</b> ${selected.meal.name}</p>
            <p><b>Package:</b> ${selected.pkg.name}</p>
            <p><b>Duration:</b> ${selected.price.duration} Days</p>
            <p><b>Calories:</b> ${selected.price.calorie}</p>
            <p><b>Price:</b> AED ${selected.price.price}</p>
          </div>
          <div class="col-md-6">
            <p><b>Customer:</b> ${selected.customer?.name || ""}</p>
            <p><b>Email:</b> ${selected.customer?.email || ""}</p>
            <p><b>Phone:</b> ${selected.customer?.phone || ""}</p>
            ${
              selected.address
                ? `<p><b>Address ID:</b> #${selected.address.id}</p>`
                : `<p class="text-danger">No address selected!</p>`
            }
          </div>
        </div>
      </div>
    `;
    $("#summaryCard").html(summaryHTML);
  }

  // Back navigation
  $("#backToCustomer").on("click", () => showStep("#step-customer"));
  $("#backToMeal").on("click", () => showStep("#step-meal"));
  $("#backToPackage").on("click", () => showStep("#step-package"));
  $("#backToPrice").on("click", () => showStep("#step-price"));
});
