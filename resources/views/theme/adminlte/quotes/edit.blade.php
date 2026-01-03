@extends('theme.adminlte.layouts.app')

@section('content-header')
    <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
            <h1 class="m-0">Edit Quote</h1>
            <small class="text-muted">Update quote #{{ $quote->quote_number }}.</small>
        </div>
        <div class="col-sm-6 d-flex justify-content-end" style="gap:10px;">
            <a href="{{ route('quotes.show', [$company->uuid, $quote->id]) }}" class="btn btn-info">
                <i class="fas fa-eye mr-1"></i> View
            </a>
            <a href="{{ route('quotes.index', $company->uuid) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>
@endsection

@section('content')
    <form action="{{ route('quotes.update', [$company->uuid, $quote->id]) }}" class="ajax-form" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            {{-- LEFT: QUOTE INFO --}}
            <div class="col-lg-8">

                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> Quote Information</h3>
                    </div>

                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Quote Number</label>
                                    <input type="text" class="form-control" value="{{ $quote->quote_number }}" disabled>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status_id" class="form-control @error('status_id') is-invalid @enderror">
                                        @foreach ($statuses as $st)
                                            <option value="{{ $st->id }}" {{ old('status_id', $quote->status_id) == $st->id ? 'selected' : '' }}>
                                                {{ $st->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Customer</label>
                                    <select name="customer_id"
                                        class="form-control @error('customer_id') is-invalid @enderror">
                                        <option value="">-- Walk-in Customer --</option>
                                        @foreach ($customers as $cust)
                                            <option value="{{ $cust->id }}" {{ old('customer_id', $quote->customer_id) == $cust->id ? 'selected' : '' }}>
                                                {{ $cust->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Currency</label>
                                    <select name="currency_id"
                                        class="form-control @error('currency_id') is-invalid @enderror">
                                        @foreach ($currencies as $curr)
                                            <option value="{{ $curr->id }}" {{ old('currency_id', $quote->currency_id) == $curr->id ? 'selected' : '' }}>
                                                {{ $curr->code }} - {{ $curr->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('currency_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Payment Terms</label>
                                    <select name="payment_term_id"
                                        class="form-control @error('payment_term_id') is-invalid @enderror">
                                        <option value="">-- No Payment Term --</option>
                                        @foreach ($paymentTerms as $term)
                                            <option value="{{ $term->id }}" {{ old('payment_term_id', $quote->payment_term_id) == $term->id ? 'selected' : '' }}>
                                                {{ $term->name }} ({{ $term->due_days }} days)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('payment_term_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Valid Until</label>
                                    <input type="date" name="valid_until"
                                        value="{{ old('valid_until', $quote->valid_until ? \Carbon\Carbon::parse($quote->valid_until)->format('Y-m-d') : '') }}"
                                        class="form-control @error('valid_until') is-invalid @enderror">
                                    @error('valid_until')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            {{-- RIGHT: ACTIONS --}}
            <div class="col-lg-4">

                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Quote Summary</h3>
                    </div>

                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td>Subtotal:</td>
                                <td class="text-right">{{ number_format($quote->subtotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Tax:</td>
                                <td class="text-right">{{ number_format($quote->tax_total, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Discount:</td>
                                <td class="text-right">{{ number_format($quote->discount_total, 2) }}</td>
                            </tr>
                            <tr class="border-top">
                                <td class="font-weight-bold">Grand Total:</td>
                                <td class="text-right font-weight-bold">{{ number_format($quote->grand_total, 2) }}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="card-footer d-flex justify-content-end" style="gap:10px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Update Quote
                        </button>
                        <a href="{{ route('quotes.index', $company->uuid) }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </form>
@endsection