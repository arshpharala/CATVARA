@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">{{ isset($category) ? 'Edit Category' : 'Create Category' }}</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.catalog.categories.index') }}" class="btn btn-default">
          <i class="fas fa-arrow-left"></i> Back
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="card card-primary">
    <form
      action="{{ isset($category) ? company_route('company.catalog.categories.update', ['category' => $category->id]) : company_route('company.catalog.categories.store') }}"
      method="POST">
      @csrf
      @if (isset($category))
        @method('PUT')
      @endif

      <div class="card-body">
        <div class="form-group">
          <label for="name">Category Name</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
            placeholder="Enter name" value="{{ old('name', $category->name ?? '') }}">
          @error('name')
            <span class="error invalid-feedback">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="parent_id">Parent Category</label>
          <select class="form-control select2" name="parent_id">
            <option value="">None</option>
            @foreach ($categories as $parent)
              <option value="{{ $parent->id }}"
                {{ old('parent_id', $category->parent_id ?? '') == $parent->id ? 'selected' : '' }}>
                {{ $parent->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label>Allowed Attributes (for Product Variants)</label>
          <select name="attributes[]" class="form-control select2" multiple>
            @foreach ($attributes as $attr)
              <option value="{{ $attr->id }}"
                {{ isset($category) && $category->attributes->contains($attr->id) ? 'selected' : '' }}>
                {{ $attr->name }} ({{ $attr->code }})
              </option>
            @endforeach
          </select>
          <small class="form-text text-muted">Only selected attributes will be available when creating products in this
            category.</small>
        </div>

        <div class="form-group">
          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
              {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
            <label class="custom-control-label" for="is_active">Active</label>
          </div>
        </div>
      </div>

      <div class="card-footer">
        <button type="submit" class="btn btn-primary">Save Category</button>
      </div>
    </form>
  </div>
@endsection
