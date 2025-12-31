@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="container-fluid">
    <div class="row mb-3 align-items-center">
      <div class="col-sm-7">
        <h1 class="page-title">User Profile</h1>
        <div class="page-subtitle">Review user identity and manage company access.</div>
      </div>
      <div class="col-sm-5 text-sm-right mt-2 mt-sm-0">
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-ui">
          <i class="fas fa-arrow-left"></i> Back
        </a>
        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary btn-ui ml-2">
          <i class="fas fa-edit"></i> Edit
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  @php
    $photo = $user->profile_photo
        ? asset('storage/' . $user->profile_photo)
        : asset('theme/adminlte/dist/img/user2-160x160.jpg');
  @endphp

  <div class="container-fluid">
    <div class="row">

      {{-- Left --}}
      <div class="col-lg-4 col-xl-3">
        <div class="card card-ui shadow-sm">
          <div class="card-header">
            <h3 class="card-title mb-0">
              <i class="fas fa-user mr-2 text-primary"></i>Identity
            </h3>
          </div>
          <div class="card-body text-center">
            <img class="img-circle elevation-2 border" src="{{ $photo }}"
              style="width:110px;height:110px;object-fit:cover;border-width:3px !important;" alt="User profile">

            <h5 class="mt-3 mb-0 font-weight-bold">{{ $user->name }}</h5>
            <div class="text-muted small">{{ $user->email }}</div>

            <div class="mt-3">
              <span class="badge badge-light border px-2 py-1 text-uppercase">
                <i class="fas fa-shield-alt mr-1 text-muted"></i>{{ str_replace('_', ' ', $user->user_type) }}
              </span>

              @if ($user->is_active)
                <span class="badge badge-success px-2 py-1 ml-1"><i class="fas fa-check mr-1"></i>Active</span>
              @else
                <span class="badge badge-danger px-2 py-1 ml-1"><i class="fas fa-times mr-1"></i>Inactive</span>
              @endif
            </div>

            <hr>

            <ul class="list-group list-group-unbordered text-left small mb-0">
              <li class="list-group-item px-0">
                <b>Last Login</b>
                <span class="float-right text-dark font-weight-bold">
                  {{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->format('d M, Y h:i A') : 'Never' }}
                </span>
              </li>
              <li class="list-group-item px-0">
                <b>Created On</b>
                <span class="float-right text-dark font-weight-bold">{{ $user->created_at?->format('d M, Y') }}</span>
              </li>
              <li class="list-group-item px-0 border-bottom-0">
                <b>Companies</b>
                <span class="float-right text-primary font-weight-bold">{{ $user->companies->count() }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      {{-- Right --}}
      <div class="col-lg-8 col-xl-9">
        <div class="card card-ui shadow-sm">
          <div class="card-header">
            <h3 class="card-title mb-0">
              <i class="fas fa-building mr-2 text-primary"></i>Company Access
            </h3>
          </div>

          <div class="card-body">

            {{-- Provision --}}
            <div class="card card-ui shadow-none border mb-3">
              <div class="card-header">
                <h3 class="card-title mb-0">
                  <i class="fas fa-link mr-2 text-primary"></i>Provision Access
                </h3>
              </div>
              <div class="card-body py-3">
                <form id="assignForm" action="{{ route('users.assignCompany', $user->id) }}" method="POST">
                  @csrf

                  <div class="row align-items-end">
                    <div class="col-lg-4 col-md-6 mb-2">
                      <label class="small font-weight-bold text-muted text-uppercase">Company</label>
                      <select name="company_id" id="companySelect" class="form-control custom-select" required>
                        <option value="">-- Select Company --</option>
                        @foreach ($companies as $c)
                          <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }})</option>
                        @endforeach
                      </select>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-2">
                      <label class="small font-weight-bold text-muted text-uppercase">Role</label>
                      <select name="role_id" id="roleSelect" class="form-control custom-select" required>
                        <option value="">-- Select Role --</option>
                      </select>
                    </div>

                    <div class="col-lg-3 col-md-12 mb-2">
                      <label class="small font-weight-bold text-muted text-uppercase d-block">Flags</label>
                      <div class="d-flex pt-1" style="gap:18px;">
                        <div class="custom-control custom-switch">
                          <input type="checkbox" name="is_owner" value="1" class="custom-control-input"
                            id="isOwner">
                          <label class="custom-control-label small font-weight-bold" for="isOwner">Owner</label>
                        </div>
                        <div class="custom-control custom-switch">
                          <input type="checkbox" name="is_active" value="1" class="custom-control-input"
                            id="isActive" checked>
                          <label class="custom-control-label small font-weight-bold" for="isActive">Enabled</label>
                        </div>
                      </div>
                    </div>

                    <div class="col-lg-2 col-md-12 mb-2 text-right">
                      <button class="btn btn-primary btn-ui btn-block" type="submit">
                        <i class="fas fa-save"></i> Save
                      </button>
                    </div>

                  </div>
                </form>
              </div>
            </div>

            {{-- Existing access --}}
            <div class="table-responsive">
              <table class="table table-hover border mb-0">
                <thead>
                  <tr>
                    <th class="px-4">Company</th>
                    <th class="text-center">Owner</th>
                    <th class="text-center">Status</th>
                    <th class="text-right px-4">Action</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($user->companies as $c)
                    <tr>
                      <td class="px-4 py-3">
                        <div class="d-flex align-items-center">
                          <div class="rounded border mr-3 d-flex align-items-center justify-content-center"
                            style="width:38px;height:38px;background:#f8f9fa;">
                            <i class="fas fa-building text-muted"></i>
                          </div>
                          <div>
                            <div class="font-weight-bold text-dark">{{ $c->name }}</div>
                            <div class="text-muted small">{{ $c->code }}</div>
                          </div>
                        </div>
                      </td>

                      <td class="text-center">
                        {!! $c->pivot->is_owner
                            ? '<span class="badge badge-info px-2 py-1">Owner</span>'
                            : '<span class="text-muted small">No</span>' !!}
                      </td>

                      <td class="text-center">
                        {!! $c->pivot->is_active
                            ? '<span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i>Active</span>'
                            : '<span class="badge badge-secondary px-2 py-1">Disabled</span>' !!}
                      </td>

                      <td class="text-right px-4">
                        <form method="POST" action="{{ route('users.removeCompany', $user->id) }}" class="d-inline">
                          @csrf
                          <input type="hidden" name="company_id" value="{{ $c->id }}">
                          <button class="btn btn-outline-danger btn-sm btn-ui" type="submit"
                            onclick="return confirm('Revoke all access for this company?')">
                            <i class="fas fa-unlink"></i> Revoke
                          </button>
                        </form>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="4" class="text-center py-5 text-muted">
                        <i class="fas fa-info-circle mr-1"></i> No company access assigned.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

          </div>
        </div>
      </div>

    </div>
  </div>
@endsection

@push('scripts')
  <script>
    $(function() {
      $('#companySelect').on('change', function() {
        const companyId = $(this).val();
        const $roleSelect = $('#roleSelect');

        if (!companyId) {
          $roleSelect.html('<option value="">-- Select Role --</option>');
          return;
        }

        $roleSelect.html('<option value="">Loading roles...</option>');

        $.get('{{ route('users.roles.byCompany') }}', {
            company_id: companyId
          })
          .done(function(roles) {
            let html = '<option value="">-- Select Role --</option>';
            roles.forEach(r => html += `<option value="${r.id}">${r.name}</option>`);
            $roleSelect.html(html);
          })
          .fail(function() {
            $roleSelect.html('<option value="">Failed to load roles</option>');
          });
      });
    });
  </script>
@endpush
