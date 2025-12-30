@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2 align-items-center">
    <div class="col-sm-6">
      <h1 class="m-0">Create Company</h1>
      <small class="text-muted">Add a new company record.</small>
    </div>
    <div class="col-sm-6 d-flex justify-content-end">
      <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Back
      </a>
    </div>
  </div>
@endsection

@section('content')
  <form action="{{ route('companies.store') }}" class="ajax-form" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row">
      {{-- LEFT: COMPANY INFO --}}
      <div class="col-lg-8">

        <div class="card card-outline card-secondary">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-building mr-1"></i> Company Information</h3>
          </div>

          <div class="card-body">

            <div class="form-group">
              <label>Company Name <span class="text-danger">*</span></label>
              <input type="text" name="name" value="{{ old('name') }}"
                class="form-control @error('name') is-invalid @enderror" placeholder="e.g. London Trade">
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label>Legal Name <span class="text-danger">*</span></label>
              <input type="text" name="legal_name" value="{{ old('legal_name') }}"
                class="form-control @error('legal_name') is-invalid @enderror" placeholder="e.g. London Trade Limited">
              @error('legal_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Company Code <span class="text-danger">*</span></label>
                  <input type="text" name="code" value="{{ old('code') }}"
                    class="form-control @error('code') is-invalid @enderror" placeholder="e.g. UK-TRADE">
                  <small class="text-muted">Unique internal code (recommended uppercase).</small>
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
                      <option value="{{ $st->id }}" {{ old('company_status_id') == $st->id ? 'selected' : '' }}>
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
              <input type="text" name="website_url" value="{{ old('website_url') }}"
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
                  <input type="text" name="invoice_prefix" value="{{ old('invoice_prefix') }}"
                    class="form-control @error('invoice_prefix') is-invalid @enderror" placeholder="e.g. INV">
                  @error('invoice_prefix')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>Invoice Postfix</label>
                  <input type="text" name="invoice_postfix" value="{{ old('invoice_postfix') }}"
                    class="form-control @error('invoice_postfix') is-invalid @enderror" placeholder="e.g. 2025">
                  @error('invoice_postfix')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>Quote Prefix</label>
                  <input type="text" name="quote_prefix" value="{{ old('quote_prefix') }}"
                    class="form-control @error('quote_prefix') is-invalid @enderror" placeholder="e.g. QT">
                  @error('quote_prefix')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>Quote Postfix</label>
                  <input type="text" name="quote_postfix" value="{{ old('quote_postfix') }}"
                    class="form-control @error('quote_postfix') is-invalid @enderror" placeholder="e.g. 2025">
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
              <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror"
                placeholder="Full address">{{ old('address') }}</textarea>
              @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label>Tax Number</label>
              <input type="text" name="tax_number" value="{{ old('tax_number') }}"
                class="form-control @error('tax_number') is-invalid @enderror" placeholder="VAT / TRN / GST etc">
              @error('tax_number')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

      </div>

      {{-- RIGHT: LOGO + ACTIONS --}}
      <div class="col-lg-4">

        <div class="card card-outline card-secondary">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-image mr-1"></i> Company Logo</h3>
          </div>

          <div class="card-body">
            <div class="form-group">
              <label>Logo (optional)</label>
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

            <div class="mt-3">
              <img id="logoPreview" src="{{ asset('theme/adminlte/dist/img/AdminLTELogo.png') }}"
                class="img-fluid img-thumbnail" style="max-height: 140px; object-fit: contain;" alt="Preview">
            </div>
          </div>

          <div class="card-footer d-flex justify-content-end" style="gap:10px;">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save mr-1"></i> Save
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

      // AdminLTE custom-file-label update
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
