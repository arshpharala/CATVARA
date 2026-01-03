@extends('theme.adminlte.layouts.app')

@section('content-header')
    <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
            <h1 class="m-0">Quote #{{ $quote->quote_number }}</h1>
            <small class="text-muted">View quote details.</small>
        </div>
        <div class="col-sm-6 d-flex justify-content-end" style="gap:10px;">
            @if(!$quote->status || !$quote->status->is_final)
                <a href="{{ route('quotes.edit', [$company->uuid, $quote->id]) }}" class="btn btn-primary">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
            @endif
            <a href="{{ route('quotes.index', $company->uuid) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        {{-- LEFT: QUOTE DETAILS --}}
        <div class="col-lg-8">

            {{-- QUOTE INFO --}}
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> Quote Information</h3>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th style="width: 40%;">Quote Number</th>
                                    <td><span class="font-weight-bold">{{ $quote->quote_number }}</span></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        @php
                                            $code = strtoupper($quote->status->code ?? '');
                                            $badge = 'secondary';
                                            if ($code === 'DRAFT')
                                                $badge = 'secondary';
                                            if ($code === 'SENT')
                                                $badge = 'info';
                                            if ($code === 'ACCEPTED')
                                                $badge = 'success';
                                            if ($code === 'EXPIRED')
                                                $badge = 'warning';
                                            if ($code === 'CANCELLED')
                                                $badge = 'danger';
                                        @endphp
                                        <span class="badge badge-{{ $badge }}">{{ $quote->status->name ?? $code }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Customer</th>
                                    <td>
                                        @if($customer)
                                            <a href="{{ route('customers.show', [$company->uuid, $customer->id]) }}">
                                                {{ $customer->display_name }}
                                            </a>
                                        @else
                                            <span class="text-muted">Walk-in Customer</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Payment Terms</th>
                                    <td>{{ $quote->payment_term_name ?? '—' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th style="width: 40%;">Created</th>
                                    <td>{{ $quote->created_at?->format('d-M-Y h:i A') ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Valid Until</th>
                                    <td>
                                        @if($quote->valid_until)
                                            @php $validDate = \Carbon\Carbon::parse($quote->valid_until); @endphp
                                            <span class="{{ $validDate->isPast() ? 'text-danger' : '' }}">
                                                {{ $validDate->format('d-M-Y') }}
                                                @if($validDate->isPast())
                                                    <i class="fas fa-exclamation-triangle ml-1"></i>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Sent At</th>
                                    <td>{{ $quote->sent_at ? \Carbon\Carbon::parse($quote->sent_at)->format('d-M-Y h:i A') : '—' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Accepted At</th>
                                    <td>{{ $quote->accepted_at ? \Carbon\Carbon::parse($quote->accepted_at)->format('d-M-Y h:i A') : '—' }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- QUOTE ITEMS --}}
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-1"></i> Line Items</h3>
                </div>

                <div class="card-body p-0">
                    @if($quote->items->count() > 0)
                        <table class="table table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th>Product</th>
                                    <th style="width: 15%;" class="text-right">Unit Price</th>
                                    <th style="width: 10%;" class="text-center">Qty</th>
                                    <th style="width: 15%;" class="text-right">Tax</th>
                                    <th style="width: 15%;" class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($quote->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $item->product_name }}</strong>
                                            @if($item->variant_description)
                                                <br><small class="text-muted">{{ $item->variant_description }}</small>
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-right">{{ number_format($item->tax_amount, 2) }}</td>
                                        <td class="text-right font-weight-bold">{{ number_format($item->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p class="mb-0">No line items added yet.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- RIGHT: TOTALS --}}
        <div class="col-lg-4">

            <div class="card card-outline card-info">
                <div class="card-header bg-info">
                    <h3 class="card-title text-white"><i class="fas fa-calculator mr-1"></i> Quote Summary</h3>
                </div>

                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-right">{{ number_format($quote->subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Tax Total:</td>
                            <td class="text-right">{{ number_format($quote->tax_total, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Discount:</td>
                            <td class="text-right text-danger">-{{ number_format($quote->discount_total, 2) }}</td>
                        </tr>
                        <tr class="border-top">
                            <td class="font-weight-bold h5 mb-0">Grand Total:</td>
                            <td class="text-right font-weight-bold h5 mb-0">{{ number_format($quote->grand_total, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Quick Info</h3>
                </div>

                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li><strong>UUID:</strong> <code class="small">{{ $quote->uuid }}</code></li>
                        <li><strong>Items:</strong> {{ $quote->items->count() }}</li>
                        <li><strong>Company:</strong> {{ $company->name }}</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
@endsection