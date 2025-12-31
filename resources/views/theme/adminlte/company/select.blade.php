@extends('theme.adminlte.layouts.app')

@section('content-header')
<div class="row mb-2">
  <div class="col-sm-12">
    <h1 class="m-0">Select Company</h1>
    <small class="text-muted">Choose the company context you want to operate in.</small>
  </div>
</div>
@endsection

@section('content')
@php
  $currentCompanyId = session('current_company_id');
@endphp

<div class="card card-outline card-secondary">
  <div class="card-header d-flex align-items-center justify-content-between">
    <h3 class="card-title">
      <i class="fas fa-building mr-1"></i> Company Directory
    </h3>

    <div class="card-tools">
      <span class="badge badge-light border">
        {{ $companies->count() }} Companies
      </span>
    </div>
  </div>

  <div class="card-body">
    @if(session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
      <table class="table table-hover table-bordered mb-0">
        <thead class="bg-light text-uppercase small text-muted">
          <tr>
            <th style="width:60px;">Logo</th>
            <th>Company</th>
            <th style="width:160px;">Status</th>
            <th style="width:190px;">Action</th>
          </tr>
        </thead>
        <tbody class="align-middle">
          @forelse($companies as $company)
            @php
              $logo = $company->logo
                ? asset('storage/'.$company->logo)
                : asset('theme/adminlte/dist/img/AdminLTELogo.png');

              $statusCode = strtoupper($company->status?->code ?? '');
              $badge = 'secondary';
              if ($statusCode === 'ACTIVE') $badge = 'success';
              if ($statusCode === 'SUSPENDED') $badge = 'warning';
              if ($statusCode === 'EXPIRED' || $statusCode === 'CLOSED') $badge = 'danger';

              $isCurrent = ($currentCompanyId && (int)$currentCompanyId === (int)$company->id);
            @endphp

            <tr class="{{ $isCurrent ? 'table-active' : '' }}">
              <td class="text-center">
                <img src="{{ $logo }}"
                     class="img-sm"
                     alt="Logo"
                     style="border-radius:6px; border:1px solid #e9ecef; object-fit:cover;">
              </td>

              <td>
                <div class="d-flex align-items-start justify-content-between">
                  <div>
                    <div class="font-weight-bold text-dark">{{ $company->name }}</div>
                    <div class="text-muted small">{{ $company->legal_name ?? '—' }}</div>

                    <div class="mt-1">
                      <span class="badge badge-light border">Code: {{ $company->code }}</span>
                      @if($company->website_url)
                        <a href="{{ $company->website_url }}" target="_blank" class="ml-2 small text-primary">
                          <i class="fas fa-external-link-alt mr-1"></i> Website
                        </a>
                      @endif
                    </div>
                  </div>

                  @if($isCurrent)
                    <span class="badge badge-info mt-1">
                      <i class="fas fa-check mr-1"></i> Current
                    </span>
                  @endif
                </div>
              </td>

              <td>
                <span class="badge badge-{{ $badge }} p-2">
                  {{ $company->status?->name ?? 'N/A' }}
                </span>
              </td>

              <td>
                <form method="POST" action="{{ route('company.select.store') }}">
                  @csrf
                  <input type="hidden" name="company_uuid" value="{{ $company->uuid }}">
                  <button type="submit" class="btn btn-primary btn-sm btn-block">
                    <i class="fas fa-check mr-1"></i> Use Company
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-muted py-5">
                <i class="fas fa-building fa-2x mb-2 d-block opacity-25"></i>
                No companies assigned to your account.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3 text-muted small">
      <i class="fas fa-info-circle mr-1"></i>
      If you do not see your company here, contact a Super Admin to grant access.
    </div>
  </div>
</div>
@endsection
