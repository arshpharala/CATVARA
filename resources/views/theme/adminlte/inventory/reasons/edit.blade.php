@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Edit Reason</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.inventory.reasons.index') }}" class="btn btn-default">
          <i class="fas fa-arrow-left"></i> Back
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title">Reason Details</h3>
        </div>
        <form action="{{ company_route('company.inventory.reasons.update', ['reason' => $reason->id]) }}" method="POST">
          @csrf
          @method('PUT')
          <div class="card-body">
            <div class="form-group">
              <label>Reason Name *</label>
              <input type="text" class="form-control" name="name" value="{{ $reason->name }}" required>
            </div>
            <div class="form-group">
              <label>Code *</label>
              <input type="text" class="form-control" name="code" value="{{ $reason->code }}" required>
            </div>
            <div class="form-group">
              <label>Effect on Stock *</label>
              <select class="form-control" name="type" required>
                <option value="in" {{ $reason->is_increase ? 'selected' : '' }}>Increase Stock (+)</option>
                <option value="out" {{ !$reason->is_increase ? 'selected' : '' }}>Decrease Stock (-)</option>
              </select>
            </div>
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                {{ $reason->is_active ? 'checked' : '' }}>
              <label class="custom-control-label" for="is_active">Active</label>
            </div>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-primary">Update Reason</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
