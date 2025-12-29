

    $(document).on("change", "#province-select", function() {
      const provinceId = $(this).val();
      $("#city-select").html('<option value="">Loading...</option>');
      $("#area-select").html('<option value="">Select your area</option>');

      if (provinceId) {
        $.get(`${appUrl}/ajax/cities/${provinceId}`, function(data) {
          let options = '<option value="">Select your city</option>';
          data.forEach((city) => {
            options += `<option value="${city.id}">${city.name}</option>`;
          });
          $("#city-select").html(options);
        });
      }
    });

    $(document).on("change", "#city-select", function() {
      const cityId = $(this).val();
      $("#area-select").html('<option value="">Loading...</option>');

      if (cityId) {
        $.get(`${appUrl}/ajax/areas/${cityId}`, function(data) {
          let options = '<option value="">Select your area</option>';
          data.forEach((area) => {
            options += `<option value="${area.id}">${area.name}</option>`;
          });
          $("#area-select").html(options);
        });
      }
    });
