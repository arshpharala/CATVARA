@extends('theme.adminlte.layouts.app')

@section('content-header')
    <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
            <h1 class="m-0">Create Customer</h1>
            <small class="text-muted">Add a new customer to {{ $company->name }}.</small>
        </div>
        <div class="col-sm-6 d-flex justify-content-end">
            <a href="{{ route('customers.index', $company->uuid) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>
@endsection

@section('content')
    <form action="{{ route('customers.store', $company->uuid) }}" class="ajax-form" method="POST">
        @csrf

        <div class="row">
            {{-- LEFT: CUSTOMER INFO --}}
            <div class="col-lg-8">

                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user mr-1"></i> Customer Information</h3>
                    </div>

                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Display Name <span class="text-danger">*</span></label>
                                    <input type="text" name="display_name" value="{{ old('display_name') }}"
                                        class="form-control @error('display_name') is-invalid @enderror"
                                        placeholder="e.g. John Smith or Acme Corp">
                                    @error('display_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Type <span class="text-danger">*</span></label>
                                    <select name="type" class="form-control @error('type') is-invalid @enderror">
                                        <option value="INDIVIDUAL" {{ old('type') == 'INDIVIDUAL' ? 'selected' : '' }}>
                                            Individual</option>
                                        <option value="COMPANY" {{ old('type') == 'COMPANY' ? 'selected' : '' }}>Company
                                        </option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" value="{{ old('email') }}"
                                        class="form-control @error('email') is-invalid @enderror"
                                        placeholder="customer@example.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" name="phone" value="{{ old('phone') }}"
                                        class="form-control @error('phone') is-invalid @enderror"
                                        placeholder="+1 234 567 8900">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-building mr-1"></i> Company Details (Optional)</h3>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Legal Name</label>
                                    <input type="text" name="legal_name" value="{{ old('legal_name') }}"
                                        class="form-control @error('legal_name') is-invalid @enderror"
                                        placeholder="e.g. Acme Corporation Ltd">
                                    <small class="text-muted">Official registered name (if different from display
                                        name).</small>
                                    @error('legal_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tax Number</label>
                                    <input type="text" name="tax_number" value="{{ old('tax_number') }}"
                                        class="form-control @error('tax_number') is-invalid @enderror"
                                        placeholder="VAT / TRN / GST etc">
                                    @error('tax_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- RIGHT: NOTES + STATUS --}}
            <div class="col-lg-4">

                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-sticky-note mr-1"></i> Notes & Status</h3>
                    </div>

                    <div class="card-body">
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror"
                                placeholder="Internal notes about this customer...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" class="custom-control-input"
                                    id="isActiveSwitch" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="isActiveSwitch">Active</label>
                            </div>
                            <small class="text-muted">Inactive customers won't appear in dropdowns.</small>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end" style="gap:10px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Save
                        </button>
                        <a href="{{ route('customers.index', $company->uuid) }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </form>
@endsection