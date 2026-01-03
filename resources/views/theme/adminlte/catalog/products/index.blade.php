@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Products</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.catalog.products.create') }}" class="btn btn-primary">
          <i class="fas fa-plus"></i> Add Product
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="card">
    <div class="card-header">
      <div class="row">
        <div class="col-md-3">
          <select id="filter_category" class="form-control select2">
            <option value="">All Categories</option>
            @foreach ($categories as $cat)
              <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
    <div class="card-body">
      <table class="table table-bordered table-hover" id="products-table">
        <thead>
          <tr>
            <th width="50">ID</th>
            <th>Product</th>
            <th>Category</th>
            <th>Variants</th>
            <th width="100">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    $(function() {
      var table = $('#products-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "{{ company_route('company.catalog.products.index') }}",
          data: function(d) {
            d.category_id = $('#filter_category').val();
          }
        },
        columns: [{
            data: 'DT_RowIndex',
            name: 'DT_RowIndex',
            orderable: false,
            searchable: false
          },
          {
            data: 'name',
            name: 'name'
          },
          {
            data: 'category_name',
            name: 'category.name'
          }, // search by relation
          {
            data: 'variants_count',
            name: 'variants_count',
            searchable: false
          },
          {
            data: 'action',
            name: 'action',
            orderable: false,
            searchable: false
          },
        ],
        order: [
          [1, 'asc']
        ]
      });

      $('#filter_category').change(function() {
        table.draw();
      });
    });
  </script>
@endpush
