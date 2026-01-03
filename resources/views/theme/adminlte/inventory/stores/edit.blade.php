@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Edit Store</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.inventory.stores.index') }}" class="btn btn-default">
          <i class="fas fa-arrow-left"></i> Back
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title">Store Details</h3>
        </div>
        <form action="{{ company_route('company.inventory.stores.update', ['store' => $store->id]) }}" method="POST">
          @csrf
          @method('PUT')
          <div class="card-body">
            <div class="form-group">
              <label>Name *</label>
              <input type="text" class="form-control" name="name" value="{{ $store->name }}" required>
            </div>
            <div class="form-group">
              <label>Code</label>
              <input type="text" class="form-control" name="code" value="{{ $store->code }}">
            </div>
            <div class="form-group">
              <label>Address</label>
              <textarea class="form-control" name="address" rows="3">{{ $store->address }}</textarea>
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input type="text" class="form-control" name="phone" value="{{ $store->phone }}">
            </div>
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                {{ $store->is_active ? 'checked' : '' }}>
              <label class="custom-control-label" for="is_active">Active</label>
            </div>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-primary">Update Store</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
