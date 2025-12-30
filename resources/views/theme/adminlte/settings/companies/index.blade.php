@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2 align-items-center">
    <div class="col-sm-6">
      <h1 class="m-0">Companies</h1>
      <small class="text-muted">Overview and management.</small>
    </div>
    <div class="col-sm-6 d-flex justify-content-end">
      <a href="{{ route('companies.create') }}" class="btn btn-primary">
        <i class="fas fa-plus mr-1"></i> Create Company
      </a>
    </div>
  </div>
@endsection

@section('content')
  {{-- TOP STATS --}}
  <div class="row">
    <div class="col-lg-3 col-md-6">
      <div class="small-box bg-info">
        <div class="inner">
          <h3 id="statAllCompanies">0</h3>
          <p>All Companies</p>
        </div>
        <div class="icon"><i class="fas fa-building"></i></div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="small-box bg-success">
        <div class="inner">
          <h3 id="statActiveCompanies">0</h3>
          <p>Active Companies</p>
        </div>
        <div class="icon"><i class="fas fa-check-circle"></i></div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="small-box bg-warning">
        <div class="inner">
          <h3 id="statSuspendedCompanies">0</h3>
          <p>Suspended Companies</p>
        </div>
        <div class="icon"><i class="fas fa-pause-circle"></i></div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="small-box bg-danger">
        <div class="inner">
          <h3 id="statExpiredCompanies">0</h3>
          <p>Expired Companies</p>
        </div>
        <div class="icon"><i class="fas fa-calendar-times"></i></div>
      </div>
    </div>
  </div>

  {{-- FILTERS CARD --}}
  <div class="card card-outline card-secondary">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-filter mr-1"></i> Filters
      </h3>
    </div>

    <div class="card-body">
      {{-- Row 1: fields --}}
      <div class="row">
        <div class="col-md-3">
          <label class="mb-1">Company Status</label>
          <select id="filterStatus" class="form-control">
            <option value="">All Status</option>
            @foreach ($statuses as $st)
              <option value="{{ $st->id }}">{{ $st->name }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <hr class="my-3">

      {{-- Row 2: buttons (separate row as requested) --}}
      <div class="row">
        <div class="col-12 d-flex justify-content-start" style="gap:10px;">
          <button type="button" id="btnApplyFilters" class="btn btn-primary">
            <i class="fas fa-search mr-1"></i> Apply
          </button>
          <button type="button" id="btnClearFilters" class="btn btn-outline-secondary">
            <i class="fas fa-times mr-1"></i> Clear Filters
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE CARD --}}
  <div class="card card-outline card-secondary">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-list mr-1"></i> Company List
      </h3>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover data-table w-100">
          <thead class="thead-light">
            <tr>
              <th>Logo</th>
              <th>Company</th>
              <th>Legal Name</th>
              <th>Code</th>
              <th>Status</th>
              <th>Website</th>
              <th>Users</th>
              <th>Created</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    $(function() {

      function getFilters() {
        return {
          company_status_id: $('#filterStatus').val()
        };
      }

      function loadStats() {
        $.get('{{ route('companies.stats') }}', getFilters(), function(res) {
          $('#statAllCompanies').text(res.all_companies ?? 0);
          $('#statActiveCompanies').text(res.active_companies ?? 0);
          $('#statSuspendedCompanies').text(res.suspended_companies ?? 0);
          $('#statExpiredCompanies').text(res.expired_companies ?? 0);
        });
      }

      const table = $('.data-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        pageLength: 25,
        order: [
          [7, 'desc']
        ], // Created
        ajax: {
          url: '{{ route('companies.index') }}',
          data: function(d) {
            const f = getFilters();
            d.company_status_id = f.company_status_id;
          }
        },
        columns: [{
            data: 'logo',
            name: 'companies.logo',
            orderable: false,
            searchable: false
          },
          {
            data: 'name',
            name: 'companies.name'
          },
          {
            data: 'legal_name',
            name: 'companies.legal_name'
          },
          {
            data: 'code',
            name: 'companies.code'
          },
          {
            data: 'company_status_badge',
            name: 'companies.company_status_id',
          },
          {
            data: 'website_url',
            name: 'companies.website_url'
          },
          {
            data: 'users_count',
            name: 'users_count',
            searchable: false
          },
          {
            data: 'created_at',
            name: 'companies.created_at'
          },
          {
            data: 'action',
            name: 'action',
            orderable: false,
            searchable: false
          },
        ]
      });

      // initial
      loadStats();

      $('#btnApplyFilters').on('click', function() {
        table.ajax.reload();
        loadStats();
      });

      $('#btnClearFilters').on('click', function() {
        $('#filterStatus').val('');
        table.ajax.reload();
        loadStats();
      });

    });
  </script>
@endpush
