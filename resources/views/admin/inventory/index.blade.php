@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Inventory Management</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.inventory.transfers.create') }}" class="btn btn-success">
          <i class="fas fa-exchange-alt"></i> New Transfer
        </a>
        <a href="{{ company_route('company.inventory.inventory.create') }}" class="btn btn-primary">
          <i class="fas fa-plus-minus"></i> Adjust Stock
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  {{-- DASHBOARD CARDS --}}
  <div class="row">
    <div class="col-lg-3 col-6">
      <div class="small-box bg-info">
        <div class="inner">
          <h3>{{ $stats['total_skus'] }}</h3>
          <p>Total SKUs</p>
        </div>
        <div class="icon"><i class="fas fa-barcode"></i></div>
      </div>
    </div>
    <div class="col-lg-3 col-6">
      <div class="small-box bg-success">
        <div class="inner">
          <h3>{{ $stats['total_units'] }}</h3>
          <p>Total Units</p>
        </div>
        <div class="icon"><i class="fas fa-boxes"></i></div>
      </div>
    </div>
    <div class="col-lg-3 col-6">
      <div class="small-box bg-warning">
        <div class="inner">
          <h3>{{ $stats['low_stock'] }}</h3>
          <p>Low Stock Alerts</p>
        </div>
        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
      </div>
    </div>
    <div class="col-lg-3 col-6">
      <div class="small-box bg-danger">
        <div class="inner">
          <h3>{{ $stats['out_of_stock'] }}</h3>
          <p>Out of Stock</p>
        </div>
        <div class="icon"><i class="fas fa-ban"></i></div>
      </div>
    </div>
  </div>

  {{-- STOCK LEVELS TABLE --}}
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Stock Levels by Location</h3>
      <div class="card-tools">
        <select id="location-filter" class="form-control form-control-sm" style="width: 200px;">
          <option value="">All Locations</option>
          @foreach ($locations as $loc)
            <option value="{{ $loc->id }}">{{ $loc->locatable->name ?? $loc->type }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="card-body">
      <table id="balances-table" class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>SKU</th>
            <th>Product</th>
            <th>Location</th>
            <th>Qty On Hand</th>
            <th>Last Movement</th>
            <th>Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

  {{-- RECENT TRANSFERS --}}
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Recent Transfers</h3>
      <div class="card-tools">
        <a href="{{ company_route('company.inventory.transfers.index') }}" class="btn btn-sm btn-default">View All</a>
      </div>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm">
        <thead>
          <tr>
            <th>Reference</th>
            <th>From</th>
            <th>To</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recentTransfers as $transfer)
            <tr>
              <td><a
                  href="{{ company_route('company.inventory.transfers.show', $transfer) }}">{{ $transfer->reference }}</a>
              </td>
              <td>{{ $transfer->fromLocation->locatable->name ?? '-' }}</td>
              <td>{{ $transfer->toLocation->locatable->name ?? '-' }}</td>
              <td><span
                  class="badge badge-{{ $transfer->status->code == 'CLOSED' ? 'success' : 'info' }}">{{ $transfer->status->name }}</span>
              </td>
              <td>{{ $transfer->created_at->format('M d, Y') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted">No recent transfers</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
    $(function() {
      var table = $('#balances-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: '{{ company_route('company.inventory.balances.data') }}',
          data: function(d) {
            d.location_id = $('#location-filter').val();
          }
        },
        columns: [{
            data: 'sku',
            name: 'productVariant.sku'
          },
          {
            data: 'product_name',
            name: 'productVariant.product.name'
          },
          {
            data: 'location_name',
            name: 'location.locatable.name'
          },
          {
            data: 'quantity',
            name: 'quantity',
            className: 'text-right'
          },
          {
            data: 'last_movement',
            name: 'last_movement_at'
          },
          {
            data: 'actions',
            name: 'actions',
            orderable: false,
            searchable: false
          }
        ]
      });

      $('#location-filter').change(function() {
        table.ajax.reload();
      });
    });
  </script>
@endsection
