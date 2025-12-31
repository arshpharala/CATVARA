@extends('theme.adminlte.layouts.app')

@section('content-header')
<div class="container-fluid">
  <div class="row mb-3 align-items-center">
    <div class="col-sm-6">
      <h1 class="m-0 font-weight-bold text-dark">Create Role</h1>
      <p class="text-muted small mb-0">Company: <b>{{ $company->name }}</b></p>
    </div>
    <div class="col-sm-6 text-right">
      <a href="{{ route('company.settings.roles.index', ['company' => $company->uuid]) }}" class="btn btn-white border shadow-sm px-3">
        <i class="fas fa-arrow-left mr-1 text-muted"></i> Back
      </a>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
  <form action="{{ route('company.settings.roles.store', ['company' => $company->uuid]) }}" method="POST" id="roleForm">
    @csrf

    @include('theme.adminlte.settings.roles.partials._form', [
      'role' => null,
      'modules' => $modules,
      'selected' => []
    ])

  </form>
</div>
@endsection

