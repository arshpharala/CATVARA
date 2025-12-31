@extends('theme.adminlte.layouts.app')

@section('content-header')
<div class="container-fluid">
  <div class="row mb-3 align-items-center">
    <div class="col-sm-6">
      <h1 class="m-0 font-weight-bold text-dark">Create Permission</h1>
      <p class="text-muted small mb-0">Define access actions for system modules.</p>
    </div>
    <div class="col-sm-6 text-right">
      <a href="{{ route('permissions.index') }}" class="btn btn-white border shadow-sm px-3">
        <i class="fas fa-arrow-left mr-1 text-muted"></i> Back
      </a>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
  <form action="{{ route('permissions.store') }}" method="POST">
    @csrf
    @include('theme.adminlte.settings.permissions.partials._form', [
      'permission' => null,
      'modules' => $modules
    ])
  </form>
</div>
@endsection

