@extends('theme.adminlte.layouts.app')

@section('content-header')
<div class="container-fluid">
  <div class="row mb-3 align-items-center">
    <div class="col-sm-7">
      <h1 class="page-title">Edit User</h1>
      <div class="page-subtitle">Update profile information and access attributes.</div>
    </div>
    <div class="col-sm-5 text-sm-right mt-2 mt-sm-0">
      <a href="{{ route('users.show', $user->id) }}" class="btn btn-outline-secondary btn-ui">
        <i class="fas fa-arrow-left"></i> Back to Profile
      </a>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="container-fluid">

  <form action="{{ route('users.update', $user->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    @php
      $photo = $user->profile_photo ? asset('storage/'.$user->profile_photo) : asset('theme/adminlte/dist/img/user2-160x160.jpg');
    @endphp

    <div class="row">
      {{-- Left --}}
      <div class="col-lg-4">

        <div class="card card-ui shadow-sm">
          <div class="card-header">
            <h3 class="card-title mb-0">
              <i class="fas fa-user-circle mr-2 text-primary"></i>User Summary
            </h3>
          </div>
          <div class="card-body text-center">
            <div class="position-relative d-inline-block">
              <img id="previewImage"
                   src="{{ $photo }}"
                   class="img-circle elevation-2 border"
                   style="width:120px;height:120px;object-fit:cover;border-width:3px !important;"
                   alt="Profile Photo">
              <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-secondary' }} position-absolute shadow-sm"
                    style="bottom: 6px; right: 6px; padding: 6px 10px; border-radius: 50px; border: 2px solid #fff;">
                {{ $user->is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>

            <h5 class="mt-3 mb-0 font-weight-bold">{{ $user->name }}</h5>
            <div class="text-muted small">{{ $user->email }}</div>

            <div class="mt-3">
              <span class="badge badge-light border px-2 py-1">
                <i class="fas fa-shield-alt mr-1 text-muted"></i>{{ $user->user_type }}
              </span>
              <span class="badge badge-light border px-2 py-1 ml-1">
                <i class="far fa-calendar-alt mr-1 text-muted"></i> {{ $user->created_at?->format('M Y') }}
              </span>
            </div>
          </div>
        </div>

        <div class="card card-ui shadow-sm mt-3">
          <div class="card-body">
            <label class="small font-weight-bold text-muted text-uppercase">Profile Photo</label>
            <div class="custom-file">
              <input type="file"
                     name="profile_photo"
                     class="custom-file-input @error('profile_photo') is-invalid @enderror"
                     id="photoInput"
                     accept="image/*">
              <label class="custom-file-label" for="photoInput">Choose file</label>
              @error('profile_photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <small class="text-muted d-block mt-2">Recommended: 250×250 JPG/PNG.</small>
          </div>
        </div>

      </div>

      {{-- Right --}}
      <div class="col-lg-8">

        <div class="card card-ui shadow-sm">
          <div class="card-header">
            <h3 class="card-title mb-0">
              <i class="fas fa-user-edit mr-2 text-primary"></i>Profile Settings
            </h3>
          </div>

          <div class="card-body">
            <div class="row">
              <div class="col-md-6 form-group">
                <label class="small font-weight-bold text-muted text-uppercase">
                  Full Name <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                  </div>
                  <input name="name"
                         value="{{ old('name', $user->name) }}"
                         class="form-control @error('name') is-invalid @enderror"
                         placeholder="John Doe">
                  @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>

              <div class="col-md-6 form-group">
                <label class="small font-weight-bold text-muted text-uppercase">
                  Email Address <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                  </div>
                  <input type="email"
                         name="email"
                         value="{{ old('email', $user->email) }}"
                         class="form-control @error('email') is-invalid @enderror"
                         placeholder="email@company.com">
                  @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 form-group">
                <label class="small font-weight-bold text-muted text-uppercase">New Password</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                  </div>
                  <input type="password"
                         name="password"
                         class="form-control @error('password') is-invalid @enderror"
                         placeholder="Leave blank to keep current">
                  @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>

              <div class="col-md-3 form-group">
                <label class="small font-weight-bold text-muted text-uppercase">User Type</label>
                <select name="user_type" class="form-control custom-select">
                  <option value="ADMIN" {{ old('user_type', $user->user_type) === 'ADMIN' ? 'selected' : '' }}>ADMIN</option>
                  <option value="SUPER_ADMIN" {{ old('user_type', $user->user_type) === 'SUPER_ADMIN' ? 'selected' : '' }}>SUPER_ADMIN</option>
                </select>
              </div>

              <div class="col-md-3 form-group">
                <label class="small font-weight-bold text-muted text-uppercase">Status</label>
                <div class="pt-2">
                  <div class="custom-control custom-switch">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           class="custom-control-input"
                           id="isActiveSwitch"
                           {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                    <label class="custom-control-label font-weight-bold" for="isActiveSwitch">Active</label>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <div class="card-footer bg-white border-top py-3 d-flex justify-content-end" style="gap:10px;">
            <a href="{{ route('users.show', $user->id) }}" class="btn btn-outline-secondary btn-ui">
              <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary btn-ui px-4">
              <i class="fas fa-check-circle"></i> Update User
            </button>
          </div>

        </div>

      </div>
    </div>

  </form>
</div>
@endsection

@push('scripts')
<script>
$(function() {
  $('#photoInput').on('change', function() {
    const file = this.files && this.files[0] ? this.files[0] : null;
    if (!file) return;

    $(this).next('.custom-file-label').text(file.name);

    const reader = new FileReader();
    reader.onload = function(e) {
      $('#previewImage').attr('src', e.target.result);
    };
    reader.readAsDataURL(file);
  });
});
</script>
@endpush
