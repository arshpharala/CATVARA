@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Categories</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.catalog.categories.create') }}" class="btn btn-primary">
          <i class="fas fa-plus"></i> Add Category
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Name</th>
            <th>Slug</th>
            <th>Parent</th>
            <th style="width: 150px">Status</th>
            <th style="width: 150px">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($categories as $category)
            <tr>
              <td>{{ $category->name }}</td>
              <td>{{ $category->slug }}</td>
              <td>
                @if ($category->parent)
                  <span class="badge badge-info">{{ $category->parent->name }}</span>
                @else
                  -
                @endif
              </td>
              <td>
                @if ($category->is_active)
                  <span class="badge badge-success">Active</span>
                @else
                  <span class="badge badge-danger">Inactive</span>
                @endif
              </td>
              <td>
                <a href="{{ company_route('company.catalog.categories.edit', ['category' => $category->id]) }}"
                  class="btn btn-sm btn-info">
                  <i class="fas fa-edit"></i>
                </a>
                <form action="{{ company_route('company.catalog.categories.destroy', ['category' => $category->id]) }}"
                  method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center">No categories found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer clearfix">
      {{ $categories->links() }}
    </div>
  </div>
@endsection
