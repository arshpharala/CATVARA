@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Stores</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.inventory.stores.create') }}" class="btn btn-primary">
          <i class="fas fa-plus"></i> Add Store
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="card card-outline card-primary">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-striped">
          <thead>
            <tr>
              <th>Name</th>
              <th>Code</th>
              <th>Address</th>
              <th>Status</th>
              <th width="150">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($stores as $store)
              <tr>
                <td>{{ $store->name }}</td>
                <td>{{ $store->code }}</td>
                <td>{{ $store->address }}</td>
                <td>
                  <span class="badge badge-{{ $store->is_active ? 'success' : 'secondary' }}">
                    {{ $store->is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td>
                  <a href="{{ company_route('company.inventory.stores.edit', ['store' => $store->id]) }}"
                    class="btn btn-xs btn-info">
                    <i class="fas fa-edit"></i> Edit
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted">No stores found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer clearfix">
      {{ $stores->links('pagination::bootstrap-4') }}
    </div>
  </div>
@endsection
