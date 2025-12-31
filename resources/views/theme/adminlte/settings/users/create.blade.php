@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="container-fluid">
    <div class="row mb-3 align-items-center">
      <div class="col-sm-7">
        <h1 class="page-title">Create User</h1>
        <div class="page-subtitle">Register a new administrative user and define access level.</div>
      </div>
      <div class="col-sm-5 text-sm-right mt-2 mt-sm-0">
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-ui">
          <i class="fas fa-arrow-left"></i> Back to Users
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="container-fluid">

    <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
      @csrf

      <div class="row">
        {{-- Left: Identity card --}}
        <div class="col-lg-4">

          <div class="card card-ui shadow-sm">
            <div class="card-header">
              <h3 class="card-title mb-0">
                <i class="fas fa-id-badge mr-2 text-primary"></i>Identity
              </h3>
            </div>

            <div class="card-body text-center">
              <div class="mb-3">
                <img id="previewImage" src="{{ asset('theme/adminlte/dist/img/user2-160x160.jpg') }}"
                  class="img-circle elevation-2 border"
                  style="width:120px;height:120px;object-fit:cover;border-width:3px !important;" alt="Preview">
              </div>

              <div class="form-group text-left mb-0">
                <label class="small font-weight-bold text-muted text-uppercase">Profile Photo</label>
                <div class="custom-file">
                  <input type="file" name="profile_photo"
                    class="custom-file-input @error('profile_photo') is-invalid @enderror" id="photoInput"
                    accept="image/*">
                  <label class="custom-file-label" for="photoInput">Choose file</label>
                  @error('profile_photo')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <small class="text-muted d-block mt-2">
                  Recommended: 250×250 JPG/PNG.
                </small>
              </div>

            </div>
          </div>

          <div class="card card-ui shadow-sm mt-3">
            <div class="card-body">
              <div class="d-flex align-items-start">
                <div class="mr-2 text-primary"><i class="fas fa-info-circle"></i></div>
                <div class="small text-muted">
                  <b class="text-dark">SUPER_ADMIN</b> can manage system settings and access control.
                  <br>
                  <b class="text-dark">ADMIN</b> manages operational modules based on permissions.
                </div>
              </div>
            </div>
          </div>

        </div>

        {{-- Right: Form --}}
        <div class="col-lg-8">

          <div class="card card-ui shadow-sm">
            <div class="card-header">
              <h3 class="card-title mb-0">
                <i class="fas fa-user-plus mr-2 text-primary"></i>Account Details
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
                    <input name="name" value="{{ old('name') }}"
                      class="form-control @error('name') is-invalid @enderror" placeholder="John Doe">
                    @error('name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
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
                    <input type="email" name="email" value="{{ old('email') }}"
                      class="form-control @error('email') is-invalid @enderror" placeholder="email@company.com">
                    @error('email')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>

              </div>

              <div class="row">

                <div class="col-md-6 form-group">
                  <label class="small font-weight-bold text-muted text-uppercase">
                    Password <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-light"><i class="fas fa-key text-muted"></i></span>
                    </div>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                      placeholder="Minimum 8 characters">
                    @error('password')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>

                <div class="col-md-3 form-group">
                  <label class="small font-weight-bold text-muted text-uppercase">User Type</label>
                  <select name="user_type" class="form-control custom-select">
                    <option value="ADMIN" {{ old('user_type', 'ADMIN') === 'ADMIN' ? 'selected' : '' }}>ADMIN</option>
                    <option value="SUPER_ADMIN" {{ old('user_type') === 'SUPER_ADMIN' ? 'selected' : '' }}>SUPER_ADMIN
                    </option>
                  </select>
                </div>

                <div class="col-md-3 form-group">
                  <label class="small font-weight-bold text-muted text-uppercase">Status</label>
                  <div class="pt-2">
                    <div class="custom-control custom-switch">
                      <input type="checkbox" name="is_active" value="1" class="custom-control-input"
                        id="statusSwitch" {{ old('is_active', 1) ? 'checked' : '' }}>
                      <label class="custom-control-label font-weight-bold" for="statusSwitch">Active</label>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <div class="card-footer bg-white border-top py-3 d-flex justify-content-end" style="gap:10px;">
              <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-ui">
                <i class="fas fa-times"></i> Cancel
              </a>
              <button type="submit" class="btn btn-primary btn-ui px-4">
                <i class="fas fa-check-circle"></i> Create User
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
