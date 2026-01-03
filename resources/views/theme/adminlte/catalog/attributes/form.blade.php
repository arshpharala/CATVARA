@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">{{ isset($attribute) ? 'Edit Attribute' : 'Create Attribute' }}</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.catalog.attributes.index') }}" class="btn btn-default">
          <i class="fas fa-arrow-left"></i> Back
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="card card-primary">
    <form
      action="{{ isset($attribute) ? company_route('company.catalog.attributes.update', ['attribute' => $attribute->id]) : company_route('company.catalog.attributes.store') }}"
      method="POST">
      @csrf
      @if (isset($attribute))
        @method('PUT')
      @endif

      <div class="card-body">
        <div class="form-group">
          <label for="name">Attribute Name (e.g. Color)</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
            placeholder="Enter name" value="{{ old('name', $attribute->name ?? '') }}">
          @error('name')
            <span class="error invalid-feedback">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="code">Attribute Code (e.g. color)</label>
          <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code"
            placeholder="Enter code" value="{{ old('code', $attribute->code ?? '') }}"
            {{ isset($attribute) ? 'readonly' : '' }}>
          @if (isset($attribute))
            <small class="text-muted">Code cannot be changed.</small>
          @endif
          @error('code')
            <span class="error invalid-feedback">{{ $message }}</span>
          @enderror
        </div>

        @if (isset($attribute))
          <div class="form-group">
            <label>Existing Values</label>
            <table class="table table-sm table-bordered">
              <thead>
                <tr>
                  <th>Value</th>
                  <th style="width: 100px;">Active</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($attribute->values as $val)
                  <tr>
                    <td>
                      <input type="text" name="existing_values[{{ $val->id }}][value]"
                        class="form-control form-control-sm" value="{{ $val->value }}" readonly>
                    </td>
                    <td class="text-center">
                      <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="switch_{{ $val->id }}"
                          name="existing_values[{{ $val->id }}][is_active]" value="1"
                          {{ $val->is_active ? 'checked' : '' }}>
                        <label class="custom-control-label" for="switch_{{ $val->id }}"></label>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="form-group">
            <label for="new_values">Add New Values (Comma separated)</label>
            <textarea class="form-control" id="new_values" name="new_values" rows="2" placeholder="e.g. Yellow, Purple"></textarea>
          </div>
        @else
          <div class="form-group">
            <label for="values">Values (Comma separated, e.g. Red, Blue, Green)</label>
            <textarea class="form-control @error('values') is-invalid @enderror" id="values" name="values" rows="3">{{ old('values') }}</textarea>
            @error('values')
              <span class="error invalid-feedback">{{ $message }}</span>
            @enderror
          </div>
        @endif
      </div>

      <div class="card-footer">
        <button type="submit" class="btn btn-primary">Save Attribute</button>
      </div>
    </form>
  </div>
@endsection
