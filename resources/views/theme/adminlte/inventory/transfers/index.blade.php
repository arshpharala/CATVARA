@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Inventory Transfers</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.inventory.transfers.create') }}" class="btn btn-success">
          <i class="fas fa-plus"></i> New Transfer
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="card">
    <div class="card-body">
      <table id="transfers-table" class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Reference</th>
            <th>From</th>
            <th>To</th>
            <th>Items</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
    $(function() {
      $('#transfers-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ company_route('company.inventory.transfers.index') }}',
        columns: [{
            data: 'reference',
            name: 'reference'
          },
          {
            data: 'from',
            name: 'from'
          },
          {
            data: 'to',
            name: 'to'
          },
          {
            data: 'items_count',
            name: 'items_count'
          },
          {
            data: 'status_badge',
            name: 'status_badge'
          },
          {
            data: 'created_at',
            name: 'created_at'
          },
          {
            data: 'actions',
            name: 'actions',
            orderable: false
          }
        ]
      });
    });
  </script>
@endsection
