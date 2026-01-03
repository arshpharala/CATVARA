@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Create Product</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.catalog.products.index') }}" class="btn btn-default">
          <i class="fas fa-arrow-left"></i> Back
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <form id="productForm" action="{{ company_route('company.catalog.products.store') }}" method="POST">
    @csrf

    <div class="row">
      <!-- Left Column: Basic Info -->
      <div class="col-md-4">
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title">Basic Information</h3>
          </div>
          <div class="card-body">
            <div class="form-group">
              <label for="name">Product Name *</label>
              <input type="text" class="form-control" id="name" name="name" required
                placeholder="e.g. Cotton T-Shirt">
            </div>

            <div class="form-group">
              <label for="category_id">Category *</label>
              <select class="form-control select2" name="category_id" required>
                <option value="">Select Category</option>
                @foreach ($categories as $cat)
                  <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group">
              <label for="description">Description</label>
              <textarea class="form-control" name="description" rows="3"></textarea>
            </div>
          </div>
        </div>

        <div class="card card-info">
          <div class="card-header">
            <h3 class="card-title">Variant Settings</h3>
          </div>
          <div class="card-body">
            <label>Select Attributes to Generate Variants</label>
            <div class="form-group">
              @foreach ($attributes as $attr)
                <div class="mb-3">
                  <label>{{ $attr->name }}</label>
                  <select class="form-control attribute-selector" data-attr-id="{{ $attr->id }}"
                    data-attr-name="{{ $attr->name }}" multiple>
                    @foreach ($attr->values as $val)
                      <option value="{{ $val->id }}">{{ $val->value }}</option>
                    @endforeach
                  </select>
                </div>
              @endforeach
            </div>
            <button type="button" class="btn btn-success btn-block" id="btnGenerate">Generate Variants</button>
            <button type="button" class="btn btn-warning btn-block" id="btnClear">Clear Variants</button>
          </div>
        </div>
      </div>

      <!-- Right Column: Variants Table -->
      <div class="col-md-8">
        <div class="card card-outline card-success">
          <div class="card-header">
            <h3 class="card-title">Product Variants</h3>
          </div>
          <div class="card-body table-responsive p-0" style="height: 600px;">
            <table class="table table-head-fixed text-nowrap" id="variantTable">
              <thead>
                <tr>
                  <th>Variant Name</th>
                  <th>SKU</th>
                  <th>Cost Price</th>
                  <th>Selling Price</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr id="emptyRow">
                  <td colspan="5" class="text-center text-muted">No variants generated yet.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="card-footer">
            <button type="button" class="btn btn-primary float-right" id="btnSubmit">Create Product & Variants</button>
          </div>
        </div>
      </div>
    </div>
  </form>
@endsection

@push('scripts')
  <script>
    $(document).ready(function() {
      $('.select2').select2();

      // Dynamic Attribute Loading
      $('[name="category_id"]').change(function() {
        var catId = $(this).val();
        if (!catId) return;

        var url = "{{ company_route('company.catalog.categories.attributes', ['category' => ':id']) }}";
        url = url.replace(':id', catId);

        $.get(url, function(data) {
          var container = $('.card-info .card-body .form-group');
          container.empty();

          if (data.length === 0) {
            container.html('<p class="text-muted">No attributes assigned to this category.</p>');
            return;
          }

          data.forEach(function(attr) {
            var html = `
                        <div class="mb-3">
                            <label>${attr.name}</label>
                            <select class="form-control attribute-selector" 
                                data-attr-id="${attr.id}" 
                                data-attr-name="${attr.name}" 
                                multiple="multiple" style="width: 100%;">
                                ${attr.values.map(v => `<option value="${v.id}">${v.value}</option>`).join('')}
                            </select>
                        </div>
                    `;
            container.append(html);
          });

          // Re-init select2 on new elements
          $('.attribute-selector').select2({
            placeholder: "Select values..."
          });
        });
      });

      // Generate Variants
      $('#btnGenerate').click(function() {
        let selectedAttrs = [];
        $('.attribute-selector').each(function() {
          let attrId = $(this).data('attr-id');
          let values = $(this).select2('data'); // Get full data objects
          if (values.length > 0) {
            selectedAttrs.push({
              id: attrId,
              values: values.map(v => ({
                id: v.id,
                text: v.text
              }))
            });
          }
        });

        if (selectedAttrs.length === 0) {
          alert("Please select at least one attribute value.");
          return;
        }

        let combinations = cartesian(selectedAttrs.map(a => a.values));
        let tbody = $('#variantTable tbody');
        tbody.empty();

        combinations.forEach((combo, index) => {
          if (!Array.isArray(combo)) combo = [combo];

          let name = combo.map(c => c.text).join(' / ');
          let attrIds = combo.map(c => c.id);

          let rowId = 'row_' + Date.now() + '_' + index;

          let tr = `
                    <tr id="${rowId}" class="variant-row">
                        <td>
                            <strong>${name}</strong>
                            <input type="hidden" name="variants[${index}][name]" value="${name}">
                            ${attrIds.map(id => `<input type="hidden" name="variants[${index}][attributes][]" value="${id}">`).join('')}
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" name="variants[${index}][sku]" placeholder="Auto-gen">
                        </td>
                        <td>
                            <input type="number" step="0.01" class="form-control form-control-sm" name="variants[${index}][cost]" placeholder="0.00">
                        </td>
                         <td>
                            <input type="number" step="0.01" class="form-control form-control-sm" name="variants[${index}][price]" placeholder="0.00">
                        </td>
                        <td>
                            <button type="button" class="btn btn-xs btn-danger" onclick="$('#${rowId}').remove()">X</button>
                        </td>
                    </tr>
                `;
          tbody.append(tr);
        });
      });

      $('#btnClear').click(function() {
        $('#variantTable tbody').html(
          '<tr id="emptyRow"><td colspan="5" class="text-center text-muted">No variants generated yet.</td></tr>'
          );
      });

      // Cartesian Helper
      function cartesian(args) {
        var r = [],
          max = args.length - 1;

        function helper(arr, i) {
          for (var j = 0, l = args[i].length; j < l; j++) {
            var a = arr.slice(0);
            a.push(args[i][j]);
            if (i == max) r.push(a);
            else helper(a, i + 1);
          }
        }
        helper([], 0);
        return r;
      }

      // AJAX Submit
      $('#btnSubmit').click(function() {
        let formData = $('#productForm').serialize();
        if (!$('#name').val()) {
          alert('Name is required');
          return;
        }

        $.ajax({
          url: $('#productForm').attr('action'),
          method: 'POST',
          data: formData,
          success: function(res) {
            if (res.success) {
              window.location.href = res.redirect;
            }
          },
          error: function(err) {
            alert('Error: ' + (err.responseJSON ? err.responseJSON.message :
            'Currently there is an error'));
          }
        });
      });
    });
  </script>
@endpush
