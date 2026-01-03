@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Create Transfer</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.inventory.transfers.index') }}" class="btn btn-default">
          <i class="fas fa-arrow-left"></i> Back
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <form action="{{ company_route('company.inventory.transfers.store') }}" method="POST">
    @csrf
    <div class="row">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Transfer Details</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>From Location <span class="text-danger">*</span></label>
                  <select name="from_location_id" class="form-control select2" required>
                    <option value="">-- Select Source --</option>
                    @foreach ($locations as $loc)
                      <option value="{{ $loc->id }}">{{ $loc->locatable->name ?? $loc->type }}
                        ({{ ucfirst($loc->type) }})</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>To Location <span class="text-danger">*</span></label>
                  <select name="to_location_id" class="form-control select2" required>
                    <option value="">-- Select Destination --</option>
                    @foreach ($locations as $loc)
                      <option value="{{ $loc->id }}">{{ $loc->locatable->name ?? $loc->type }}
                        ({{ ucfirst($loc->type) }})</option>
                    @endforeach
                  </select>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Notes</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
            </div>
          </div>
        </div>

        {{-- ITEMS --}}
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Transfer Items</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-sm btn-success" id="add-item">
                <i class="fas fa-plus"></i> Add Item
              </button>
            </div>
          </div>
          <div class="card-body">
            <table class="table table-bordered" id="items-table">
              <thead>
                <tr>
                  <th>Variant</th>
                  <th width="150">Quantity</th>
                  <th width="50"></th>
                </tr>
              </thead>
              <tbody>
                <tr class="item-row">
                  <td>
                    <select name="items[0][variant_id]" class="form-control select2" required>
                      <option value="">-- Select Variant --</option>
                      @foreach ($variants as $v)
                        <option value="{{ $v->id }}">{{ $v->sku }} - {{ $v->product->name ?? 'Unknown' }}
                        </option>
                      @endforeach
                    </select>
                  </td>
                  <td>
                    <input type="number" name="items[0][quantity]" class="form-control" min="0.01" step="0.01"
                      required>
                  </td>
                  <td>
                    <button type="button" class="btn btn-sm btn-danger remove-item"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Actions</h3>
          </div>
          <div class="card-body">
            <button type="submit" class="btn btn-primary btn-block">
              <i class="fas fa-save"></i> Create Transfer (Draft)
            </button>
          </div>
        </div>
      </div>
    </div>
  </form>
@endsection

@section('scripts')
  <script>
    var itemIndex = 1;
    $('#add-item').click(function() {
      var row = `
        <tr class="item-row">
          <td>
            <select name="items[${itemIndex}][variant_id]" class="form-control select2" required>
              <option value="">-- Select Variant --</option>
              @foreach ($variants as $v)
                <option value="{{ $v->id }}">{{ $v->sku }} - {{ $v->product->name ?? 'Unknown' }}</option>
              @endforeach
            </select>
          </td>
          <td>
            <input type="number" name="items[${itemIndex}][quantity]" class="form-control" min="0.01" step="0.01" required>
          </td>
          <td>
            <button type="button" class="btn btn-sm btn-danger remove-item"><i class="fas fa-trash"></i></button>
          </td>
        </tr>
    `;
      $('#items-table tbody').append(row);
      itemIndex++;
    });

    $(document).on('click', '.remove-item', function() {
      if ($('.item-row').length > 1) {
        $(this).closest('tr').remove();
      }
    });
  </script>
@endsection
