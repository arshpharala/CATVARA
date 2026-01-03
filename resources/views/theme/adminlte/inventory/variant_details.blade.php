@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0 text-dark">
        <i class="fas fa-boxes text-muted mr-2"></i> {{ $variant->sku }}
        <small class="text-muted ml-2" style="font-size: 1rem;">{{ $variant->product->name }}</small>
      </h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.catalog.products.edit', ['product' => $variant->product_id]) }}"
          class="btn btn-outline-secondary mr-2">
          <i class="fas fa-arrow-left mr-1"></i> Back to Product
        </a>
        <button type="button" class="btn btn-primary btn-transfer-stock">
          <i class="fas fa-exchange-alt mr-1"></i> Transfer Stock
        </button>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="row">
    <div class="col-12">
      @if (session('success'))
        <div class="alert alert-success alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
          <h5><i class="icon fas fa-check"></i> Success!</h5>
          {{ session('success') }}
        </div>
      @endif
      @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
          <h5><i class="icon fas fa-ban"></i> Error!</h5>
          {{ session('error') }}
        </div>
      @endif
      @if ($errors->any())
        <div class="alert alert-danger alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
          <h5><i class="icon fas fa-exclamation-triangle"></i> Validation Error!</h5>
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>
  </div>
  <div class="row">
    {{-- LEFT COLUMN: INFO --}}
    <div class="col-md-3">

      {{-- Total Stock Widget --}}
      <div class="small-box bg-{{ $balances->sum('quantity') > 0 ? 'info' : 'danger' }}">
        <div class="inner">
          <h3>{{ (float) $balances->sum('quantity') }}</h3>
          <p>Total Stock on Hand</p>
        </div>
        <div class="icon">
          <i class="fas fa-cubes"></i>
        </div>
      </div>

      {{-- DETAILS CARD --}}
      <div class="card card-outline card-primary">
        <div class="card-header">
          <h3 class="card-title">Variant Details</h3>
        </div>
        <div class="card-body p-0">
          <table class="table table-striped">
            <tbody>
              @foreach ($variant->attributeValues as $val)
                <tr>
                  <td><b>{{ $val->attribute->name }}</b></td>
                  <td class="text-right">{{ $val->value }}</td>
                </tr>
              @endforeach
              <tr>
                <td><b>Cost Price</b></td>
                <td class="text-right">
                  @php
                    $currency = $variant->prices->first()->currency->symbol ?? '$';
                  @endphp
                  {{ $currency }}{{ number_format($variant->cost_price, 2) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- RIGHT COLUMN: STOCK & LOGS --}}
    <div class="col-md-9">
      {{-- LOCATION BALANCES --}}
      <div class="card card-outline card-success">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-warehouse mr-1"></i> Inventory by Location</h3>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
              <thead class="bg-light">
                <tr>
                  <th>Location</th>
                  <th>Type</th>
                  <th class="text-center">On Hand</th>
                  <th class="text-right" style="width: 200px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($locations as $loc)
                  @php
                    $bal = $balances->where('inventory_location_id', $loc->id)->first();
                    $qty = $bal ? $bal->quantity : 0;
                  @endphp
                  <tr>
                    <td class="align-middle">
                      <span class="font-weight-bold">{{ $loc->locatable->name }}</span>
                      @if ($loc->locatable->code)
                        <br><small class="text-muted">{{ $loc->locatable->code }}</small>
                      @endif
                    </td>
                    <td class="align-middle"><span class="badge badge-light border">{{ ucfirst($loc->type) }}</span></td>
                    <td class="text-center align-middle">
                      <span class="badge {{ $qty > 0 ? 'badge-success' : 'badge-light border' }}"
                        style="font-size: 1.1rem; padding: 0.5em 0.8em;">
                        {{ (float) $qty }}
                      </span>
                    </td>
                    <td class="text-right align-middle">
                      <div class="btn-group">
                        <button class="btn btn-sm btn-success btn-add-stock" data-location-id="{{ $loc->id }}"
                          data-location-name="{{ $loc->locatable->name }}">
                          <i class="fas fa-plus"></i> Add
                        </button>
                        <button class="btn btn-sm btn-default btn-adjust-stock" data-location-id="{{ $loc->id }}"
                          title="Adjust / Remove">
                          <i class="fas fa-cog"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- AUDIT TRAIL --}}
      <div class="card card-outline card-navy">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-history mr-1"></i> Movement History</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
          </div>
        </div>
        <div class="card-body">
          <table id="movements-table" class="table table-bordered table-striped" style="width: 100%;">
            <thead>
              <tr>
                <th>Date</th>
                <th>Reason</th>
                <th>Reference</th> {{-- Added Reference Column --}}
                <th>Location</th>
                <th>Qty</th>
                <th>User</th>
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

        <div class="modal-header bg-light">
          <h4 class="modal-title" id="modalTitle">Adjust Stock</h4>
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

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Action</label>
                <select class="form-control" name="type" id="modal_type" required>
                  <option value="add">Add Stock (+)</option>
                  <option value="remove">Remove Stock (-)</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Quantity</label>
                <input type="number" step="0.01" class="form-control" name="quantity" min="0.01" required
                  placeholder="0.00">
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Reason / Reference</label>
            <input type="text" class="form-control" name="reason"
              placeholder="e.g. Purchase Order, Stock count, Broken..." required>
          </div>
        </div>
        <div class="modal-footer bg-light justify-content-between">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="btnSubmitAdjust">Update Stock</button>
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

        <div class="modal-header bg-light">
          <h4 class="modal-title">Quick Transfer</h4>
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
        <div class="modal-footer bg-light justify-content-between">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Execute Transfer</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    $(function() {
      // Init DataTable only if it exists (hidden if empty)
      if ($('#movements-table').length) {
        $('#movements-table').DataTable({
          processing: true,
          serverSide: true,
          autoWidth: false,
          searching: false, // Minimal mode for sidebar feel
          lengthChange: false,
          pageLength: 10,
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
              data: 'reference',
              name: 'reference_type', // Searchable by type/id? logic in controller
              orderable: false
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
      }

      // Add Stock Button Logic
      $('.btn-add-stock').click(function() {
        var locId = $(this).data('location-id');
        var locName = $(this).data('location-name');

        $('#modalTitle').text('Add Stock: ' + locName);
        $('#modal_location_id').val(locId); // Select location
        $('#modal_type').val('add'); // Force Add

        // Optional: Disable location and type to simplify UX? 
        // User may want to change it though. Let's keep it flexible but pre-filled.

        $('#adjustStockModal').modal('show');
      });

      // Adjust/Manage Button Logic
      $('.btn-adjust-stock').click(function() {
        var locId = $(this).data('location-id');

        $('#modalTitle').text('Adjust Stock');
        if (locId) {
          $('#modal_location_id').val(locId);
        }
        $('#modal_type').val('add'); // Default

        $('#adjustStockModal').modal('show');
      });

      $('.btn-transfer-stock').click(function() {
        $('#transferStockModal').modal('show');
      });

      // Select2 inside modals if needed, but standard select is often fine for small lists
      // If locations list is huge, we should init select2.
      // $('select.form-control').select2({ theme: 'bootstrap4' });
    });
  </script>
@endpush
