@extends('theme.adminlte.layouts.app')

@section('content-header')
<div class="container-fluid">
  <div class="row mb-3 align-items-center">
    <div class="col-sm-6">
      <h1 class="m-0 font-weight-bold text-dark">Create Module</h1>
      <p class="text-muted small mb-0">Add a new master module used to group permissions.</p>
    </div>
    <div class="col-sm-6 text-right">
      <a href="{{ route('modules.index') }}" class="btn btn-white border shadow-sm px-3">
        <i class="fas fa-arrow-left mr-1 text-muted"></i> Back
      </a>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
  <form action="{{ route('modules.store') }}" method="POST">
    @csrf
    @include('theme.adminlte.settings.modules.partials._form', ['module' => null])
  </form>
</div>
@endsection
