@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="container-fluid">
    <div class="row mb-3 align-items-center">
      <div class="col-sm-7">
        <h1 class="page-title">User Management</h1>
        <div class="page-subtitle">Manage administrative users and permissions across companies.</div>
      </div>
      <div class="col-sm-5 text-sm-right mt-2 mt-sm-0">
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-ui">
          <i class="fas fa-user-plus"></i> Add User
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="container-fluid">

    {{-- Filters --}}
    <div class="card card-ui shadow-sm mb-3">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-2 text-primary"></i>Filters</h3>
      </div>
      <div class="card-body py-3">
        <div class="form-row align-items-center">
          <div class="col-md-3 mb-2 mb-md-0">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-light"><i class="fas fa-user-shield text-muted"></i></span>
              </div>
              <select id="filterType" class="form-control">
                <option value="">All Roles</option>
                <option value="ADMIN">ADMIN</option>
                <option value="SUPER_ADMIN">SUPER_ADMIN</option>
              </select>
            </div>
          </div>

          <div class="col-md-3 mb-2 mb-md-0">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-light"><i class="fas fa-toggle-on text-muted"></i></span>
              </div>
              <select id="filterActive" class="form-control">
                <option value="">All Statuses</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>

          <div class="col-md-6 text-md-right mt-2 mt-md-0">
            <button id="btnApply" class="btn btn-primary btn-ui">
              <i class="fas fa-filter"></i> Apply
            </button>
            <button id="btnClear" class="btn btn-outline-secondary btn-ui ml-2">
              <i class="fas fa-times"></i> Clear
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- Table --}}
    <div class="card card-ui shadow-sm">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0"><i class="fas fa-users mr-2 text-primary"></i>User Directory</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" onclick="window.location.reload()"><i
                class="fas fa-sync-alt"></i></button>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover data-table w-100 mb-0">
            <thead>
              <tr>
                <th class="px-4" style="width:70px;">Photo</th>
                <th>User</th>
                <th>Type</th>
                <th>Companies</th>
                <th class="text-center">Status</th>
                <th>Last Login</th>
                <th class="text-right px-4">Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
@endsection

@push('scripts')
  <script>
    $(function() {
      const table = $('.data-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        pageLength: 25,
        dom: '<"d-flex justify-content-between align-items-center p-3"lf>rt<"d-flex justify-content-between align-items-center p-3"ip>',
        ajax: {
          url: '{{ route('users.index') }}',
          data: function(d) {
            d.is_active = $('#filterActive').val();
            d.user_type = $('#filterType').val();
          }
        },
        columns: [{
            data: 'photo',
            orderable: false,
            searchable: false,
            className: 'px-4'
          },
          {
            data: 'name',
            render: function(data, type, row) {
              return `<div class="font-weight-bold text-dark">${data}</div>
                  <div class="text-muted small"><i class="far fa-envelope mr-1"></i>${row.email}</div>`;
            }
          },
          {
            data: 'user_type',
            name: 'user_type'
          },
          {
            data: 'companies_count',
            name: 'companies_count',
            searchable: false,
            render: (data) =>
              `<span class="badge badge-light border px-2 py-1"><i class="fas fa-building mr-1 text-muted"></i>${data}</span>`
          },
          {
            data: 'is_active',
            name: 'is_active',
            className: 'text-center'
          },
          {
            data: 'last_login_at',
            name: 'last_login_at'
          },
          {
            data: 'action',
            orderable: false,
            searchable: false,
            className: 'text-right px-4'
          },
        ],
        language: {
          search: "_INPUT_",
          searchPlaceholder: "Search users...",
          lengthMenu: "Show _MENU_",
        },
        drawCallback: function() {
          $('.dropdown-toggle').dropdown();
        }
      });

      $('.dataTables_filter input').addClass('form-control ml-2').css('width', '260px');
      $('.dataTables_length select').addClass('custom-select custom-select-sm mx-2');

      $('#btnApply').on('click', () => table.ajax.reload());
      $('#btnClear').on('click', () => {
        $('#filterActive, #filterType').val('');
        table.ajax.reload();
      });
    });
  </script>
@endpush
