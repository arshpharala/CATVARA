@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Attributes</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.catalog.attributes.create') }}" class="btn btn-primary">
          <i class="fas fa-plus"></i> Add Attribute
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
            <th>Code</th>
            <th>Values</th>
            <th style="width: 150px">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($attributes as $attribute)
            <tr>
              <td>{{ $attribute->name }}</td>
              <td><code>{{ $attribute->code }}</code></td>
              <td>
                @foreach ($attribute->values as $val)
                  <span class="badge badge-secondary">{{ $val->value }}</span>
                @endforeach
              </td>
              <td>
                <a href="{{ company_route('company.catalog.attributes.edit', ['attribute' => $attribute->id]) }}"
                  class="btn btn-sm btn-info">
                  <i class="fas fa-edit"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center">No attributes found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer clearfix">
      {{ $attributes->links() }}
    </div>
  </div>
@endsection
