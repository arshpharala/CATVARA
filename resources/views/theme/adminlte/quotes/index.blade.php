@extends('theme.adminlte.layouts.app')

@section('content-header')
    <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
            <h1 class="m-0">Quotes</h1>
            <small class="text-muted">Manage quotations for {{ $company->name }}.</small>
        </div>
        <div class="col-sm-6 d-flex justify-content-end">
            <a href="{{ route('quotes.create', $company->uuid) }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Create Quote
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
                    <h3 id="statAllQuotes">0</h3>
                    <p>All Quotes</p>
                </div>
                <div class="icon"><i class="fas fa-file-alt"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3 id="statDraftQuotes">0</h3>
                    <p>Draft</p>
                </div>
                <div class="icon"><i class="fas fa-edit"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3 id="statSentQuotes">0</h3>
                    <p>Sent</p>
                </div>
                <div class="icon"><i class="fas fa-paper-plane"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3 id="statAcceptedQuotes">0</h3>
                    <p>Accepted</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
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
                    <label class="mb-1">Status</label>
                    <select id="filterStatus" class="form-control">
                        <option value="">All Status</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st->id }}">{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="mb-1">Customer</label>
                    <select id="filterCustomer" class="form-control">
                        <option value="">All Customers</option>
                        @foreach ($customers as $cust)
                            <option value="{{ $cust->id }}">{{ $cust->display_name }}</option>
                        @endforeach
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
                <i class="fas fa-list mr-1"></i> Quote List
            </h3>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover data-table w-100">
                    <thead class="thead-light">
                        <tr>
                            <th>Quote #</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Valid Until</th>
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
                    status_id: $('#filterStatus').val(),
                    customer_id: $('#filterCustomer').val()
                };
            }

            function loadStats() {
                $.get('{{ route('quotes.stats', $company->uuid) }}', getFilters(), function (res) {
                    $('#statAllQuotes').text(res.all_quotes ?? 0);
                    $('#statDraftQuotes').text(res.draft_quotes ?? 0);
                    $('#statSentQuotes').text(res.sent_quotes ?? 0);
                    $('#statAcceptedQuotes').text(res.accepted_quotes ?? 0);
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
                    url: '{{ route('quotes.index', $company->uuid) }}',
                    data: function (d) {
                        const f = getFilters();
                        d.status_id = f.status_id;
                        d.customer_id = f.customer_id;
                    }
                },
                columns: [
                    { data: 'quote_number', name: 'quotes.quote_number' },
                    { data: 'customer_name', name: 'customers.display_name' },
                    { data: 'status_badge', name: 'quote_statuses.name' },
                    { data: 'grand_total', name: 'quotes.grand_total' },
                    { data: 'valid_until', name: 'quotes.valid_until' },
                    { data: 'created_at', name: 'quotes.created_at' },
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
                $('#filterStatus').val('');
                $('#filterCustomer').val('');
                table.ajax.reload();
                loadStats();
            });

        });
    </script>
@endpush