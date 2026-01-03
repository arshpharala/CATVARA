@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Movement History</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.inventory.inventory.index') }}" class="btn btn-default">
          <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Stock Movements (Audit Trail)</h3>
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
      <table id="movements-table" class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Date</th>
            <th>SKU</th>
            <th>Location</th>
            <th>Reason</th>
            <th>Quantity</th>
            <th>By</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
    $(function() {
      var table = $('#movements-table').DataTable({
        processing: true,
        serverSide: true,
        order: [
          [0, 'desc']
        ],
        ajax: {
          url: '{{ company_route('company.inventory.movements') }}',
          data: function(d) {
            d.location_id = $('#location-filter').val();
          }
        },
        columns: [{
            data: 'date',
            name: 'occurred_at'
          },
          {
            data: 'sku',
            name: 'sku'
          },
          {
            data: 'location_name',
            name: 'location_name'
          },
          {
            data: 'reason_name',
            name: 'reason_name'
          },
          {
            data: 'quantity',
            name: 'quantity'
          },
          {
            data: 'performed_by_name',
            name: 'performed_by_name'
          }
        ]
      });

      $('#location-filter').change(function() {
        table.ajax.reload();
      });
    });
  </script>
@endsection
