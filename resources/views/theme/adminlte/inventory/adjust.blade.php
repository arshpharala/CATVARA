@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Adjust Stock</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.inventory.index') }}" class="btn btn-default">
          <i class="fas fa-arrow-left"></i> Back
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="card card-warning">
    <div class="card-header">
      <h3 class="card-title">Manual Stock Adjustment</h3>
    </div>
    <form action="{{ company_route('company.inventory.store') }}" method="POST">
      @csrf

      <div class="card-body">
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label>Location *</label>
              <select name="inventory_location_id" class="form-control select2" required>
                @foreach ($locations as $loc)
                  <option value="{{ $loc->id }}">
                    {{ $loc->locatable->name ?? $loc->type . ' #' . $loc->id }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="col-md-5">
            <div class="form-group">
              <label>Product Variant *</label>
              <select name="product_variant_id" class="form-control select2" required>
                <option value="">Select Product...</option>
                @foreach ($variants as $v)
                  <option value="{{ $v->id }}">
                    {{ $v->sku }} | {{ $v->product->name }}
                    ({{ $v->attributeValues->pluck('value')->join(', ') }})
                  </option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="col-md-3">
            <div class="form-group">
              <label>Reason *</label>
              <select name="reason_id" class="form-control" required>
                @foreach ($reasons as $r)
                  <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->code }})</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label>Quantity Change *</label>
              <input type="number" step="0.000001" name="quantity" class="form-control" placeholder="e.g. 10 or -5"
                required>
              <small class="text-muted">Positive to add, Negative to remove.</small>
            </div>
          </div>
          <div class="col-md-8">
            <div class="form-group">
              <label>Notes</label>
              <input type="text" name="notes" class="form-control" placeholder="Optional reference">
            </div>
          </div>
        </div>
      </div>

      <div class="card-footer">
        <button type="submit" class="btn btn-warning">Process Adjustment</button>
      </div>
    </form>
  </div>
@endsection
