@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Inventory: {{ $variant->sku }}</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.catalog.products.edit', $variant->product_id) }}" class="btn btn-default">
          <i class="fas fa-arrow-left"></i> Back to Product
        </a>
        <button type="button" class="btn btn-warning btn-adjust-stock" data-variant-id="{{ $variant->id }}"
          data-variant-name="{{ $variant->sku }}">
          <i class="fas fa-dolly"></i> Adjust Stock
        </button>
        <button type="button" class="btn btn-info btn-transfer-stock" data-variant-id="{{ $variant->id }}"
          data-variant-name="{{ $variant->sku }}">
          <i class="fas fa-exchange-alt"></i> Transfer
        </button>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="row">
    <div class="col-md-3">
      {{-- DETAILS CARD --}}
      <div class="card card-primary card-outline">
        <div class="card-body box-profile">
          <h3 class="profile-username text-center">{{ $variant->sku }}</h3>
          <p class="text-muted text-center">{{ $variant->product->name }}</p>

          <ul class="list-group list-group-unbordered mb-3">
            @foreach ($variant->attributeValues as $val)
              <li class="list-group-item">
                <b>{{ $val->attribute->name }}</b> <a class="float-right">{{ $val->value }}</a>
              </li>
            @endforeach
            <li class="list-group-item">
              <b>Cost Price</b> <a class="float-right">{{ $variant->cost_price }}</a>
            </li>
          </ul>
        </div>
      </div>

      {{-- TOTAL STOCK CARD --}}
      <div class="info-box mb-3 bg-{{ $balances->sum('quantity') > 0 ? 'success' : 'danger' }}">
        <span class="info-box-icon"><i class="fas fa-cubes"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Total Stock</span>
          <span class="info-box-number">{{ $balances->sum('quantity') }}</span>
        </div>
      </div>
    </div>

    <div class="col-md-9">
      {{-- LOCATION BALANCES --}}
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Stock per Location</h3>
        </div>
        <div class="card-body p-0">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Location</th>
                <th>Type</th>
                <th class="text-center">On Hand</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($locations as $loc)
                @php
                  $bal = $balances->where('inventory_location_id', $loc->id)->first();
                  $qty = $bal ? $bal->quantity : 0;
                @endphp
                <tr>
                  <td>{{ $loc->locatable->name }}</td>
                  <td>{{ ucfirst($loc->type) }}</td>
                  <td class="text-center">
                    <span class="badge {{ $qty > 0 ? 'badge-success' : 'badge-secondary' }}" style="font-size: 1rem;">
                      {{ $qty }}
                    </span>
                  </td>
                  <td>
                    <button class="btn btn-xs btn-default btn-adjust-stock" data-variant-id="{{ $variant->id }}"
                      data-variant-name="{{ $variant->sku }}" data-location-id="{{ $loc->id }}">
                      Adjust
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      {{-- AUDIT TRAIL --}}
      <div class="card card-outline card-navy">
        <div class="card-header">
          <h3 class="card-title">Audit Trail (Movement Logs)</h3>
        </div>
        <div class="card-body">
          <table id="movements-table" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>Date</th>
                <th>Reason</th>
                <th>Location</th>
                <th>Qty</th>
                <th>Performed By</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- ADJUSTMENT MODAL --}}
  <div class="modal fade" id="adjustStockModal">
    <div class="modal-dialog">
      <form action="{{ company_route('company.inventory.store') }}" method="POST" class="modal-content">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
        <input type="hidden" name="product_variant_id" value="{{ $variant->id }}"> <!-- Fixed for this page -->

        <div class="modal-header">
          <h4 class="modal-title">Adjust Stock: {{ $variant->sku }}</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Location</label>
            <select class="form-control" name="inventory_location_id" id="modal_location_id" required>
              @foreach ($locations as $loc)
                <option value="{{ $loc->id }}">{{ $loc->locatable->name }} ({{ ucfirst($loc->type) }})</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>Adjustment Type</label>
            <select class="form-control" name="type" required>
              <option value="add">Add Stock (+)</option>
              <option value="remove">Remove Stock (-)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Quantity</label>
            <input type="number" step="0.01" class="form-control" name="quantity" min="0.01" required>
          </div>
          <div class="form-group">
            <label>Reason / Reference</label>
            <input type="text" class="form-control" name="reason" placeholder="e.g. Broken Item, Found, PO#123"
              required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Submit Adjustment</button>
        </div>
      </form>
    </div>
  </div>

  {{-- TRANSFER MODAL --}}
  <div class="modal fade" id="transferStockModal">
    <div class="modal-dialog">
      <form action="{{ company_route('company.inventory.transfer') }}" method="POST" class="modal-content">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
        <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">

        <div class="modal-header">
          <h4 class="modal-title">Quick Transfer: {{ $variant->sku }}</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>From Location</label>
            <select class="form-control" name="from_location_id" required>
              @foreach ($locations as $loc)
                <option value="{{ $loc->id }}">{{ $loc->locatable->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>To Location</label>
            <select class="form-control" name="to_location_id" required>
              @foreach ($locations as $loc)
                <option value="{{ $loc->id }}">{{ $loc->locatable->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>Quantity</label>
            <input type="number" step="0.01" class="form-control" name="quantity" min="0.01" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Execute Transfer</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
    $(function() {
      // Init DataTable
      $('#movements-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "{{ company_route('company.inventory.movements') }}",
          data: function(d) {
            d.product_variant_id = "{{ $variant->id }}";
          }
        },
        columns: [{
            data: 'date',
            name: 'occurred_at'
          },
          {
            data: 'reason_name',
            name: 'reason.name'
          },
          {
            data: 'location_name',
            name: 'location.locatable.name'
          },
          {
            data: 'quantity',
            name: 'quantity'
          },
          {
            data: 'performed_by_name',
            name: 'performer.name'
          }
        ],
        order: [
          [0, 'desc']
        ]
      });

      // Modal triggers
      $('.btn-adjust-stock').click(function() {
        var locId = $(this).data('location-id');
        if (locId) {
          $('#modal_location_id').val(locId);
        }
        $('#adjustStockModal').modal('show');
      });

      $('.btn-transfer-stock').click(function() {
        $('#transferStockModal').modal('show');
      });
    });
  </script>
@endsection
