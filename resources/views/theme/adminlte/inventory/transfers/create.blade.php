@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0 text-dark">Create Transfer</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.inventory.transfers.index') }}" class="btn btn-default">
          <i class="fas fa-arrow-left mr-1"></i> Back to Transfers
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <form action="{{ company_route('company.inventory.transfers.store') }}" method="POST">
    @csrf
    <div class="row">
      <div class="col-md-12">
        {{-- Location Selection Card --}}
        <div class="card card-primary card-outline">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-map-marker-alt mr-1"></i> Transfer Route</h3>
          </div>
          <div class="card-body">
            <div class="row align-items-center">
              {{-- FROM --}}
              <div class="col-md-5">
                <div class="form-group">
                  <label class="text-muted">From Location <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-warehouse"></i></span>
                    </div>
                    <select name="from_location_id" class="form-control select2" required style="width: 100%;">
                      <option value="">-- Select Source --</option>
                      @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}">
                          {{ $loc->locatable->name ?? $loc->type }} ({{ ucfirst($loc->type) }})
                        </option>
                      @endforeach
                    </select>
                  </div>
                </div>
              </div>

              {{-- ARROW --}}
              <div class="col-md-2 text-center d-none d-md-block">
                <i class="fas fa-arrow-right fa-2x text-muted"></i>
              </div>

              {{-- TO --}}
              <div class="col-md-5">
                <div class="form-group">
                  <label class="text-muted">To Location <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-store"></i></span>
                    </div>
                    <select name="to_location_id" class="form-control select2" required style="width: 100%;">
                      <option value="">-- Select Destination --</option>
                      @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}">
                          {{ $loc->locatable->name ?? $loc->type }} ({{ ucfirst($loc->type) }})
                        </option>
                      @endforeach
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-12">
                <div class="form-group">
                  <label>Notes / Reference</label>
                  <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Weekly resupply for Downtown Store..."></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      {{-- Items Card --}}
      <div class="col-md-12">
        <div class="card card-outline card-info">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-boxes mr-1"></i> Items to Transfer</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-sm btn-success" id="add-item">
                <i class="fas fa-plus mr-1"></i> Add Item
              </button>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-striped table-bordered mb-0" id="items-table">
                <thead class="bg-light">
                  <tr>
                    <th style="width: 60%">Product Variant</th>
                    <th style="width: 20%">Quantity</th>
                    <th style="width: 10%" class="text-center">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr class="item-row">
                    <td>
                      <select name="items[0][variant_id]" class="form-control select2" required style="width: 100%;">
                        <option value="">-- Select Variant --</option>
                        @foreach ($variants as $v)
                          <option value="{{ $v->id }}">{{ $v->sku }} - {{ $v->product->name ?? 'Unknown' }}
                          </option>
                        @endforeach
                      </select>
                    </td>
                    <td>
                      <input type="number" name="items[0][quantity]" class="form-control" min="0.01" step="0.01"
                        placeholder="0.00" required>
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Remove Item">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="p-3 bg-light border-top">
              <div class="row justify-content-end">
                <div class="col-md-3">
                  <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-save mr-1"></i> Create Transfer
                  </button>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </form>
@endsection

@push('scripts')
  <script>
    $(function() {
      // Initialize Select2 for existing items
      $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
      });

      var itemIndex = {{ count(old('items', [1])) }};

      $('#add-item').click(function(e) {
        e.preventDefault();

        var rowHtml = `
          <tr class="item-row">
            <td>
              <select name="items[${itemIndex}][variant_id]" class="form-control select2" required style="width: 100%;">
                <option value="">-- Select Variant --</option>
                @foreach ($variants as $v)
                  <option value="{{ $v->id }}">{{ $v->sku }} - {{ $v->product->name ?? 'Unknown' }}</option>
                @endforeach
              </select>
            </td>
            <td>
              <input type="number" name="items[${itemIndex}][quantity]" class="form-control" min="0.01" step="0.01" placeholder="0.00" required>
            </td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Remove Item"><i class="fas fa-trash-alt"></i></button>
            </td>
          </tr>
        `;

        var $row = $(rowHtml);
        $('#items-table tbody').append($row);

        // Initialize Select2 on the new select element with bootstrap theme
        $row.find('.select2').select2({
          theme: 'bootstrap4',
          width: '100%'
        });

        itemIndex++;
      });

      $(document).on('click', '.remove-item', function() {
        if ($('.item-row').length > 1) {
          $(this).closest('tr').remove();
        } else {
          Swal.fire({
            icon: 'warning',
            title: 'Info',
            text: 'You cannot remove the last item.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
          });
        }
      });
    });
  </script>
@endpush
