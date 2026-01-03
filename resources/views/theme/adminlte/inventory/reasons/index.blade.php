@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Inventory Reasons</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.inventory.reasons.create') }}" class="btn btn-primary">
          <i class="fas fa-plus"></i> Add Reason
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
              <th>Type</th>
              <th>Status</th>
              <th width="150">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($reasons as $reason)
              <tr>
                <td>{{ $reason->name }}</td>
                <td>{{ $reason->code }}</td>
                <td>
                  <span class="badge badge-{{ $reason->is_increase ? 'success' : 'warning' }}">
                    {{ $reason->is_increase ? 'Stock IN (+)' : 'Stock OUT (-)' }}
                  </span>
                </td>
                <td>
                  <span class="badge badge-{{ $reason->is_active ? 'success' : 'secondary' }}">
                    {{ $reason->is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td>
                  <a href="{{ company_route('company.inventory.reasons.edit', ['reason' => $reason->id]) }}"
                    class="btn btn-xs btn-info">
                    <i class="fas fa-edit"></i> Edit
                  </a>
                  {{-- Soft Delete support usually means we just archive it --}}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted">No reasons found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
