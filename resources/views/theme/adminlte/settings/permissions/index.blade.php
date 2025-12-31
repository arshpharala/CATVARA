@extends('theme.adminlte.layouts.app')

@section('content-header')
<div class="container-fluid">
  <div class="row mb-4 align-items-center">
    <div class="col-sm-6">
      <h1 class="m-0 font-weight-bold text-dark">Permissions</h1>
      <p class="text-muted small mb-0">Manage system permissions by module.</p>
    </div>
    <div class="col-sm-6 text-right">
      <a href="{{ route('permissions.create') }}" class="btn btn-primary px-4 shadow-sm">
        <i class="fas fa-plus-circle mr-1"></i> Add Permission
      </a>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="container-fluid">

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
      <div class="form-row align-items-center">
        <div class="col-md-4">
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text bg-light border-right-0">
                <i class="fas fa-layer-group text-muted"></i>
              </span>
            </div>
            <select id="filterModule" class="form-control border-left-0">
              <option value="">All Modules</option>
              @foreach($modules as $m)
                <option value="{{ $m->id }}">{{ $m->name }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="col-md-3">
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text bg-light border-right-0">
                <i class="fas fa-toggle-on text-muted"></i>
              </span>
            </div>
            <select id="filterActive" class="form-control border-left-0">
              <option value="">All Status</option>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
        </div>

        <div class="col-md-5 text-right">
          <button id="btnApply" class="btn btn-dark shadow-sm px-4">
            <i class="fas fa-filter mr-1"></i> Apply
          </button>
          <button id="btnClear" class="btn btn-link text-secondary font-weight-bold">
            Reset Filters
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom py-3">
      <div class="d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold mb-0">
          <i class="fas fa-key mr-2 text-primary"></i> Permission Directory
        </h3>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" onclick="window.location.reload()"><i class="fas fa-sync-alt"></i></button>
        </div>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover data-table w-100 mb-0">
          <thead class="bg-light text-muted text-uppercase small font-weight-bold">
            <tr>
              <th class="px-4" style="width:60px;">#</th>
              <th>Permission</th>
              <th>Slug</th>
              <th>Module</th>
              <th>Status</th>
              <th>Created</th>
              <th class="text-right px-4">Action</th>
            </tr>
          </thead>
          <tbody class="align-middle"></tbody>
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
      url: '{{ route('permissions.index') }}',
      data: function(d) {
        d.module_id = $('#filterModule').val();
        d.is_active = $('#filterActive').val();
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false, className:'px-4' },
      { data: 'name', name: 'permissions.name' },
      { data: 'slug', name: 'permissions.slug', orderable:false, searchable:true },
      { data: 'module', name: 'modules.name', orderable:false, searchable:true },
      { data: 'is_active', name: 'permissions.is_active', orderable:false, searchable:false },
      { data: 'created_at', name: 'permissions.created_at' },
      { data: 'action', orderable:false, searchable:false, className:'text-right px-4' },
    ],
    language: {
      search: "_INPUT_",
      searchPlaceholder: "Search permissions...",
      lengthMenu: "Show _MENU_",
    },
    drawCallback: function() {
      $('.dropdown-toggle').dropdown();
    }
  });

  $('.dataTables_filter input').addClass('form-control ml-2').css('width', '250px');
  $('.dataTables_length select').addClass('custom-select custom-select-sm mx-2');

  $('#btnApply').on('click', () => table.ajax.reload());
  $('#btnClear').on('click', () => {
    $('#filterModule, #filterActive').val('');
    table.ajax.reload();
  });
});
</script>
@endpush
