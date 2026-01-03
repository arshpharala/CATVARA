@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Edit Product: {{ $product->name }}</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.catalog.products.index') }}" class="btn btn-default">
          <i class="fas fa-arrow-left"></i> Back
        </a>
        <button type="submit" form="product-form" class="btn btn-success">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <form id="product-form" action="{{ company_route('company.catalog.products.update', ['product' => $product->id]) }}"
    method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row">
      {{-- LEFT COLUMN --}}
      <div class="col-md-3">
        {{-- GENERAL INFO --}}
        <div class="card card-primary card-outline">
          <div class="card-body box-profile">
            <div class="text-center">
              @php
                $img = $product->attachments->where('is_primary', true)->first();
                $src = $img ? asset('storage/' . $img->path) : asset('theme/adminlte/dist/img/default-150x150.png');
              @endphp
              <img class="profile-user-img img-fluid img-circle" src="{{ $src }}" alt="Product Image">
            </div>
            <h3 class="profile-username text-center">{{ $product->name }}</h3>
            <p class="text-muted text-center">{{ $product->slug }}</p>

            <div class="form-group">
              <label>Category</label>
              <select class="form-control" name="category_id">
                @foreach ($categories as $cat)
                  <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group">
              <label>Name</label>
              <input type="text" class="form-control" name="name" value="{{ $product->name }}">
            </div>

            <div class="form-group">
              <label>Description</label>
              <textarea class="form-control" name="description" rows="3">{{ $product->description }}</textarea>
            </div>
          </div>
        </div>

        {{-- MEDIA UPLOAD (Simple for now) --}}
        <div class="card card-outline card-info">
          <div class="card-header">
            <h3 class="card-title">Media</h3>
          </div>
          <div class="card-body">
            <input type="file" name="image" class="form-control-file">
            <small class="text-muted">Upload to replace primary image.</small>
          </div>
        </div>
      </div>

      {{-- RIGHT COLUMN (TABS) --}}
      <div class="col-md-9">
        <div class="card card-primary card-outline card-tabs">
          <div class="card-header p-0 pt-1 border-bottom-0">
            <ul class="nav nav-tabs" id="product-tabs" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="tab-variants" data-toggle="pill" href="#content-variants"
                  role="tab">Variants & Pricing</a>
              </li>

            </ul>
          </div>
          <div class="card-body">
            <div class="tab-content" id="product-tabs-content">

              {{-- VARIANTS & PRICING TAB --}}
              <div class="tab-pane fade show active" id="content-variants" role="tabpanel">
                <div class="table-responsive">
                  <table class="table table-hover table-striped">
                    <thead>
                      <tr>
                        <th>SKU</th>
                        <th>Attributes</th>
                        <th width="120">Cost ({{ $currency->symbol }})</th>
                        @foreach ($channels as $ch)
                          <th width="120">{{ $ch->name }} ({{ $currency->symbol }})</th>
                        @endforeach
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($product->variants as $variant)
                        <tr>
                          <td>
                            <input type="text" class="form-control form-control-sm"
                              name="variants[{{ $variant->id }}][sku]" value="{{ $variant->sku }}">
                          </td>
                          <td>
                            @foreach ($variant->attributeValues as $val)
                              <span class="badge badge-light border">{{ $val->attribute->name }}:
                                {{ $val->value }}</span>
                            @endforeach
                          </td>
                          <td>
                            <input type="number" step="0.01" class="form-control form-control-sm"
                              name="variants[{{ $variant->id }}][cost_price]" value="{{ $variant->cost_price }}">
                          </td>
                          {{-- DYNAMIC PRICE INPUTS FOR EACH CHANNEL --}}
                          @foreach ($channels as $ch)
                            @php
                              $price = $variant->prices->where('price_channel_id', $ch->id)->first();
                              $val = $price ? $price->price : '';
                            @endphp
                            <td>
                              <input type="number" step="0.01" class="form-control form-control-sm"
                                name="prices[{{ $variant->id }}][{{ $ch->id }}]" value="{{ $val }}"
                                placeholder="0.00">
                            </td>
                          @endforeach
                          <td>
                            <div class="custom-control custom-switch">
                              <input type="checkbox" class="custom-control-input" id="v_active_{{ $variant->id }}"
                                checked disabled>
                              <label class="custom-control-label" for="v_active_{{ $variant->id }}"></label>
                              <a href="{{ company_route('company.inventory.variant.details', ['product_variant' => $variant->id]) }}"
                                class="btn btn-sm btn-primary ml-1" target="_blank">
                                <i class="fas fa-boxes"></i> Manage Inventory
                              </a>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
                <small class="text-muted"><i class="fas fa-info-circle"></i> Prices are saved per channel. Empty prices
                  may fall back to default logic depending on configuration.</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endsection
