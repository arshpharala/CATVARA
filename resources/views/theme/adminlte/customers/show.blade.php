@extends('theme.adminlte.layouts.app')

@section('content-header')
    <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
            <h1 class="m-0">Customer Details</h1>
            <small class="text-muted">View customer information.</small>
        </div>
        <div class="col-sm-6 d-flex justify-content-end" style="gap:10px;">
            <a href="{{ route('customers.edit', [$company->uuid, $customer->id]) }}" class="btn btn-primary">
                <i class="fas fa-edit mr-1"></i> Edit
            </a>
            <a href="{{ route('customers.index', $company->uuid) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        {{-- LEFT: CUSTOMER INFO --}}
        <div class="col-lg-8">

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user mr-1"></i> Customer Information</h3>
                </div>

                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <th style="width: 30%;">Display Name</th>
                                <td>{{ $customer->display_name }}</td>
                            </tr>
                            <tr>
                                <th>Type</th>
                                <td>
                                    @if($customer->type === 'COMPANY')
                                        <span class="badge badge-primary"><i class="fas fa-building mr-1"></i> Company</span>
                                    @else
                                        <span class="badge badge-secondary"><i class="fas fa-user mr-1"></i> Individual</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>
                                    @if($customer->email)
                                        <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td>
                                    @if($customer->phone)
                                        <a href="tel:{{ $customer->phone }}">{{ $customer->phone }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Legal Name</th>
                                <td>{{ $customer->legal_name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Tax Number</th>
                                <td>{{ $customer->tax_number ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @if($customer->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created</th>
                                <td>{{ $customer->created_at?->format('d-M-Y h:i A') ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Updated</th>
                                <td>{{ $customer->updated_at?->format('d-M-Y h:i A') ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ADDRESSES --}}
            @if($customer->addresses->count() > 0)
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-map-marker-alt mr-1"></i> Addresses</h3>
                    </div>

                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Address</th>
                                    <th>Contact</th>
                                    <th>Default</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customer->addresses as $address)
                                    <tr>
                                        <td>
                                            <span class="badge badge-{{ $address->type === 'BILLING' ? 'warning' : 'info' }}">
                                                {{ $address->type }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $address->address_line_1 }}
                                            @if($address->address_line_2)<br>{{ $address->address_line_2 }}@endif
                                            @if($address->city || $address->state)
                                                <br>{{ $address->city }}{{ $address->city && $address->state ? ', ' : '' }}{{ $address->state }}
                                            @endif
                                            @if($address->postal_code || $address->country_code)
                                                <br>{{ $address->postal_code }} {{ $address->country_code }}
                                            @endif
                                        </td>
                                        <td>
                                            {{ $address->contact_name ?? '—' }}
                                            @if($address->phone)<br><small class="text-muted">{{ $address->phone }}</small>@endif
                                        </td>
                                        <td>
                                            @if($address->is_default)
                                                <span class="badge badge-success">Yes</span>
                                            @else
                                                <span class="text-muted">No</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>

        {{-- RIGHT: NOTES --}}
        <div class="col-lg-4">

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sticky-note mr-1"></i> Notes</h3>
                </div>

                <div class="card-body">
                    @if($customer->notes)
                        <p class="mb-0">{!! nl2br(e($customer->notes)) !!}</p>
                    @else
                        <p class="text-muted mb-0">No notes available.</p>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Quick Info</h3>
                </div>

                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li><strong>UUID:</strong> <code>{{ $customer->uuid }}</code></li>
                        <li><strong>Company:</strong> {{ $company->name }}</li>
                        <li><strong>Addresses:</strong> {{ $customer->addresses->count() }}</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
@endsection