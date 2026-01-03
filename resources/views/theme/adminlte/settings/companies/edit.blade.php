@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2 align-items-center">
    <div class="col-sm-6">
      <h1 class="m-0">Edit Company</h1>
      <small class="text-muted">Update company record.</small>
    </div>
    <div class="col-sm-6 d-flex justify-content-end">
      <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Back
      </a>
    </div>
  </div>
@endsection

@section('content')

  <form action="{{ route('companies.update', $company->id) }}" method="POST" class="ajax-form"
    enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row">
      {{-- LEFT --}}
      <div class="col-lg-8">

        <div class="card card-outline card-secondary">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-building mr-1"></i> Company Information</h3>
          </div>

          <div class="card-body">

            <div class="form-group">
              <label>Company Name <span class="text-danger">*</span></label>
              <input type="text" name="name" value="{{ old('name', $company->name) }}"
                class="form-control @error('name') is-invalid @enderror">
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label>Legal Name <span class="text-danger">*</span></label>
              <input type="text" name="legal_name" value="{{ old('legal_name', $company->legal_name) }}"
                class="form-control @error('legal_name') is-invalid @enderror">
              @error('legal_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Company Code <span class="text-danger">*</span></label>
                  <input type="text" name="code" value="{{ old('code', $company->code) }}"
                    class="form-control @error('code') is-invalid @enderror">
                  @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>Status <span class="text-danger">*</span></label>
                  <select name="company_status_id" class="form-control @error('company_status_id') is-invalid @enderror">
                    <option value="">-- Select Status --</option>
                    @foreach ($statuses as $st)
                      <option value="{{ $st->id }}"
                        {{ old('company_status_id', $company->company_status_id) == $st->id ? 'selected' : '' }}>
                        {{ $st->name }}
                      </option>
                    @endforeach
                  </select>
                  @error('company_status_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Website URL</label>
              <input type="text" name="website_url" value="{{ old('website_url', $company->website_url) }}"
                class="form-control @error('website_url') is-invalid @enderror" placeholder="https://example.com">
              @error('website_url')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

          </div>
        </div>

        <div class="card card-outline card-secondary">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-invoice mr-1"></i> Document Settings</h3>
          </div>

          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Invoice Prefix</label>
                  <input type="text" name="invoice_prefix"
                    value="{{ old('invoice_prefix', $company->detail?->invoice_prefix) }}"
                    class="form-control @error('invoice_prefix') is-invalid @enderror">
                  @error('invoice_prefix')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>Invoice Postfix</label>
                  <input type="text" name="invoice_postfix"
                    value="{{ old('invoice_postfix', $company->detail?->invoice_postfix) }}"
                    class="form-control @error('invoice_postfix') is-invalid @enderror">
                  @error('invoice_postfix')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>Quote Prefix</label>
                  <input type="text" name="quote_prefix"
                    value="{{ old('quote_prefix', $company->detail?->quote_prefix) }}"
                    class="form-control @error('quote_prefix') is-invalid @enderror">
                  @error('quote_prefix')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>Quote Postfix</label>
                  <input type="text" name="quote_postfix"
                    value="{{ old('quote_postfix', $company->detail?->quote_postfix) }}"
                    class="form-control @error('quote_postfix') is-invalid @enderror">
                  @error('quote_postfix')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card card-outline card-secondary">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-map-marker-alt mr-1"></i> Address & Tax</h3>
          </div>

          <div class="card-body">
            <div class="form-group">
              <label>Address</label>
              <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $company->detail?->address) }}</textarea>
              @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label>Tax Number</label>
              <input type="text" name="tax_number" value="{{ old('tax_number', $company->detail?->tax_number) }}"
                class="form-control @error('tax_number') is-invalid @enderror">
              @error('tax_number')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <div class="card card-outline card-secondary">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-money-bill-wave mr-1"></i> Financial Settings</h3>
          </div>
          <div class="card-body">
            <!-- Base Currency -->
            <div class="form-group">
              <label>Base Currency <span class="text-danger">*</span></label>
              @if ($company->base_currency_id && $company->baseCurrency)
                <!-- Display Only -->
                <input type="text" class="form-control"
                  value="{{ $company->baseCurrency->code }} - {{ $company->baseCurrency->name }}" disabled>
                <small class="text-muted"><i class="fas fa-lock mr-1"></i> Base currency cannot be changed once
                  set.</small>
              @else
                <select name="base_currency_id" class="form-control @error('base_currency_id') is-invalid @enderror">
                  <option value="">-- Select Base Currency --</option>
                  @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}"
                      {{ old('base_currency_id', $company->base_currency_id) == $currency->id ? 'selected' : '' }}>
                      {{ $currency->code }} - {{ $currency->name }}
                    </option>
                  @endforeach
                </select>
                @error('base_currency_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              @endif
            </div>

            <hr>

            <!-- Payment Terms -->
            <div class="form-group">
              <label>Enabled Payment Terms</label>
              <div class="row mt-2">
                @foreach ($paymentTerms as $term)
                  <div class="col-md-6 mb-2">
                    <div class="custom-control custom-checkbox">
                      <input class="custom-control-input" type="checkbox" id="pt_{{ $term->id }}"
                        name="payment_terms[]" value="{{ $term->id }}"
                        {{ (is_array(old('payment_terms')) && in_array($term->id, old('payment_terms'))) || $company->paymentTerms->contains($term->id) ? 'checked' : '' }}>
                      <label for="pt_{{ $term->id }}" class="custom-control-label">{{ $term->name }} <small
                          class="text-muted">({{ $term->code }})</small></label>
                    </div>
                  </div>
                @endforeach
              </div>
              @error('payment_terms')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

      </div>

      {{-- RIGHT --}}
      <div class="col-lg-4">
        <div class="card card-outline card-secondary">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-image mr-1"></i> Company Logo</h3>
          </div>

          <div class="card-body">
            <div class="form-group">
              <label>Replace Logo (optional)</label>
              <div class="custom-file">
                <input type="file" name="logo" class="custom-file-input @error('logo') is-invalid @enderror"
                  id="logoInput" accept="image/*">
                <label class="custom-file-label" for="logoInput">Choose file</label>
                @error('logo')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <small class="text-muted d-block mt-2">JPG/PNG/WEBP, max 2MB.</small>
            </div>

            @php
              $logoSrc = $company->logo
                  ? asset('storage/' . $company->logo)
                  : asset('theme/adminlte/dist/img/AdminLTELogo.png');
            @endphp

            <div class="mt-3">
              <img id="logoPreview" src="{{ $logoSrc }}" class="img-fluid img-thumbnail"
                style="max-height: 140px; object-fit: contain;" alt="Preview">
            </div>
          </div>

          <div class="card-footer d-flex justify-content-end" style="gap:10px;">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save mr-1"></i> Update
            </button>
            <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary">
              Cancel
            </a>
          </div>
        </div>
      </div>

    </div>
  </form>

@endsection

@push('scripts')
  <script>
    $(function() {
      $('#logoInput').on('change', function(e) {
        const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
        if (file) {
          $(this).next('.custom-file-label').html(file.name);

          const reader = new FileReader();
          reader.onload = function(ev) {
            $('#logoPreview').attr('src', ev.target.result);
          };
          reader.readAsDataURL(file);
        }
      });
    });
  </script>
@endpush
