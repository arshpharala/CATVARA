@extends('theme.adminlte.layouts.app')

@section('content-header')
    <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
            <h1 class="m-0">Create Quote</h1>
            <small class="text-muted">Create a new quotation for {{ $company->name }}.</small>
        </div>
        <div class="col-sm-6 d-flex justify-content-end">
            <a href="{{ route('quotes.index', $company->uuid) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>
@endsection

@section('content')
    <form action="{{ route('quotes.store', $company->uuid) }}" class="ajax-form" method="POST">
        @csrf

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
                                    <label>Customer</label>
                                    <select name="customer_id"
                                        class="form-control @error('customer_id') is-invalid @enderror">
                                        <option value="">-- Walk-in Customer --</option>
                                        @foreach ($customers as $cust)
                                            <option value="{{ $cust->id }}" {{ old('customer_id') == $cust->id ? 'selected' : '' }}>
                                                {{ $cust->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Leave empty for walk-in customer.</small>
                                    @error('customer_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Currency <span class="text-danger">*</span></label>
                                    <select name="currency_id"
                                        class="form-control @error('currency_id') is-invalid @enderror" required>
                                        <option value="">-- Select Currency --</option>
                                        @foreach ($currencies as $curr)
                                            <option value="{{ $curr->id }}" {{ old('currency_id') == $curr->id ? 'selected' : '' }}>
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
                                        <option value="">-- Select Payment Term --</option>
                                        @foreach ($paymentTerms as $term)
                                            <option value="{{ $term->id }}" {{ old('payment_term_id') == $term->id ? 'selected' : '' }}>
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
                                        value="{{ old('valid_until', now()->addDays(15)->format('Y-m-d')) }}"
                                        class="form-control @error('valid_until') is-invalid @enderror">
                                    <small class="text-muted">Quote expiry date.</small>
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
                        <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Quick Info</h3>
                    </div>

                    <div class="card-body">
                        <div class="callout callout-info">
                            <h5>Creating a Draft Quote</h5>
                            <p class="mb-0">
                                This will create a new quote in <strong>DRAFT</strong> status.
                                After creation, you can add line items and then send it to the customer.
                            </p>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end" style="gap:10px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Create Quote
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