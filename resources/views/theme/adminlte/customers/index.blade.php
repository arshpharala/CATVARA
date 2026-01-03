@extends('theme.adminlte.layouts.app')

@section('content-header')
    <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
            <h1 class="m-0">Customers</h1>
            <small class="text-muted">Manage customer records for {{ $company->name }}.</small>
        </div>
        <div class="col-sm-6 d-flex justify-content-end">
            <a href="{{ route('customers.create', $company->uuid) }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Create Customer
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
                    <h3 id="statAllCustomers">0</h3>
                    <p>All Customers</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3 id="statActiveCustomers">0</h3>
                    <p>Active Customers</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3 id="statCompanyCustomers">0</h3>
                    <p>Company Type</p>
                </div>
                <div class="icon"><i class="fas fa-building"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3 id="statIndividualCustomers">0</h3>
                    <p>Individual Type</p>
                </div>
                <div class="icon"><i class="fas fa-user"></i></div>
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
            <div class="row">
                <div class="col-md-3">
                    <label class="mb-1">Customer Type</label>
                    <select id="filterType" class="form-control">
                        <option value="">All Types</option>
                        <option value="INDIVIDUAL">Individual</option>
                        <option value="COMPANY">Company</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="mb-1">Status</label>
                    <select id="filterStatus" class="form-control">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-footer">
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
                <i class="fas fa-list mr-1"></i> Customer List
            </h3>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover data-table w-100">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
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
        $(function () {

            function getFilters() {
                return {
                    type: $('#filterType').val(),
                    is_active: $('#filterStatus').val()
                };
            }

            function loadStats() {
                $.get('{{ route('customers.stats', $company->uuid) }}', getFilters(), function (res) {
                    $('#statAllCustomers').text(res.all_customers ?? 0);
                    $('#statActiveCustomers').text(res.active_customers ?? 0);
                    $('#statCompanyCustomers').text(res.company_customers ?? 0);
                    $('#statIndividualCustomers').text(res.individual_customers ?? 0);
                });
            }

            const table = $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                pageLength: 25,
                order: [[5, 'desc']], // Created
                ajax: {
                    url: '{{ route('customers.index', $company->uuid) }}',
                    data: function (d) {
                        const f = getFilters();
                        d.type = f.type;
                        d.is_active = f.is_active;
                    }
                },
                columns: [
                    { data: 'display_name', name: 'customers.display_name' },
                    { data: 'type_badge', name: 'customers.type' },
                    { data: 'email', name: 'customers.email' },
                    { data: 'phone', name: 'customers.phone' },
                    { data: 'status_badge', name: 'customers.is_active', searchable: false },
                    { data: 'created_at', name: 'customers.created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            // Initial load
            loadStats();

            $('#btnApplyFilters').on('click', function () {
                table.ajax.reload();
                loadStats();
            });

            $('#btnClearFilters').on('click', function () {
                $('#filterType').val('');
                $('#filterStatus').val('');
                table.ajax.reload();
                loadStats();
            });

        });
    </script>
@endpush