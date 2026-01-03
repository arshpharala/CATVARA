@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Company Settings</h1>
    </div>
  </div>
@endsection

@section('content')
  <form action="{{ company_route('company.settings.general.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card card-primary card-outline card-tabs">
      <div class="card-header p-0 pt-1 border-bottom-0">
        <ul class="nav nav-tabs" id="custom-tabs-three-tab" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="tabs-general-tab" data-toggle="pill" href="#tabs-general" role="tab"
              aria-controls="tabs-general" aria-selected="true">General Information</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="tabs-finance-tab" data-toggle="pill" href="#tabs-finance" role="tab"
              aria-controls="tabs-finance" aria-selected="false">Finance & Defaults</a>
          </li>
        </ul>
      </div>
      <div class="card-body">
        <div class="tab-content" id="custom-tabs-three-tabContent">

          {{-- General Tab --}}
          <div class="tab-pane fade show active" id="tabs-general" role="tabpanel" aria-labelledby="tabs-general-tab">
            <div class="row">
              <div class="col-md-8">
                <div class="form-group row">
                  <label class="col-sm-3 col-form-label">Company Name</label>
                  <div class="col-sm-9">
                    <input type="text" class="form-control" name="name" value="{{ old('name', $company->name) }}"
                      required>
                  </div>
                </div>
                <div class="form-group row">
                  <label class="col-sm-3 col-form-label">Legal Name</label>
                  <div class="col-sm-9">
                    <input type="text" class="form-control" name="legal_name"
                      value="{{ old('legal_name', $company->legal_name) }}">
                  </div>
                </div>
                <div class="form-group row">
                  <label class="col-sm-3 col-form-label">Website</label>
                  <div class="col-sm-9">
                    <input type="url" class="form-control" name="website_url"
                      value="{{ old('website_url', $company->website_url) }}">
                  </div>
                </div>
                <div class="form-group row">
                  <label class="col-sm-3 col-form-label">Address</label>
                  <div class="col-sm-9">
                    <textarea class="form-control" name="address" rows="3">{{ old('address', $company->detail->address ?? '') }}</textarea>
                  </div>
                </div>
              </div>
              <div class="col-md-4 text-center">
                <label>Company Logo</label>
                <div class="mt-2 mb-3">
                  @if ($company->logo)
                    <img src="{{ asset('storage/' . $company->logo) }}" class="img-thumbnail" style="max-height: 150px;">
                  @else
                    <img src="{{ asset('theme/adminlte/dist/img/AdminLTELogo.png') }}" class="img-thumbnail"
                      style="max-height: 150px;">
                  @endif
                </div>
                <input type="file" name="logo" class="form-control-file">
              </div>
            </div>
          </div>

          {{-- Finance Tab --}}
          <div class="tab-pane fade" id="tabs-finance" role="tabpanel" aria-labelledby="tabs-finance-tab">
            <div class="form-group row">
              <label class="col-sm-2 col-form-label">Tax ID / VAT</label>
              <div class="col-sm-4">
                <input type="text" class="form-control" name="tax_number"
                  value="{{ old('tax_number', $company->detail->tax_number ?? '') }}">
              </div>
            </div>
            <div class="dropdown-divider"></div>
            <h5 class="mt-3 mb-3">Numbering Prefixes</h5>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label">Invoice Prefix</label>
              <div class="col-sm-2">
                <input type="text" class="form-control" name="invoice_prefix"
                  value="{{ old('invoice_prefix', $company->detail->invoice_prefix ?? '') }}" placeholder="INV-">
              </div>
            </div>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label">Quote Prefix</label>
              <div class="col-sm-2">
                <input type="text" class="form-control" name="quote_prefix"
                  value="{{ old('quote_prefix', $company->detail->quote_prefix ?? '') }}" placeholder="QT-">
              </div>
            </div>
          </div>

        </div>
      </div>
      <!-- /.card -->
      <div class="card-footer">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
      </div>
    </div>
  </form>
@endsection
