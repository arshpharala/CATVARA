@php
  $isEdit = !empty($role);
@endphp

<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 border-bottom">
    <h3 class="card-title font-weight-bold text-dark mb-0">
      <i class="fas fa-user-shield mr-2 text-primary"></i> Role Details
    </h3>
  </div>

  <div class="card-body">
    <div class="row">
      <div class="col-md-4 form-group">
        <label class="small font-weight-bold text-muted text-uppercase">Role Name <span class="text-danger">*</span></label>
        <input name="name" value="{{ old('name', $role->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Manager">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-4 form-group">
        <label class="small font-weight-bold text-muted text-uppercase">Slug</label>
        <input name="slug" value="{{ old('slug', $role->slug ?? '') }}"
               class="form-control @error('slug') is-invalid @enderror" placeholder="e.g. manager">
        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <small class="text-muted">Leave blank to auto-generate from name.</small>
      </div>

      <div class="col-md-4 form-group">
        <label class="small font-weight-bold text-muted text-uppercase">Status</label>
        <div class="pt-2">
          <div class="custom-control custom-switch">
            <input type="checkbox" name="is_active" value="1"
                   class="custom-control-input" id="isActiveSwitch"
                   {{ old('is_active', $role->is_active ?? true) ? 'checked' : '' }}>
            <label class="custom-control-label font-weight-bold" for="isActiveSwitch">Active</label>
          </div>
        </div>
      </div>
    </div>

    <hr class="my-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="font-weight-bold mb-0">
        <i class="fas fa-key mr-1 text-primary"></i> Permissions
      </h6>

      <div class="d-flex" style="gap:10px;">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSelectAll">
          <i class="fas fa-check-double mr-1"></i> Select All
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnClearAll">
          <i class="fas fa-eraser mr-1"></i> Clear All
        </button>
      </div>
    </div>

    <div class="row">
      @foreach($modules as $module)
        <div class="col-lg-4 col-md-6 mb-3">
          <div class="card border">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
              <div class="font-weight-bold text-dark small">
                <i class="fas fa-layer-group mr-1 text-muted"></i> {{ $module->name }}
              </div>

              <button type="button"
                      class="btn btn-xs btn-outline-secondary moduleToggle"
                      data-module="{{ $module->id }}">
                Toggle
              </button>
            </div>

            <div class="card-body py-2">
              @forelse($module->permissions as $perm)
                @php
                  $checked = in_array($perm->id, old('permissions', $selected ?? []), true);
                @endphp
                <div class="custom-control custom-checkbox mb-2">
                  <input type="checkbox"
                         class="custom-control-input perm-checkbox perm-module-{{ $module->id }}"
                         id="perm{{ $perm->id }}"
                         name="permissions[]"
                         value="{{ $perm->id }}"
                         {{ $checked ? 'checked' : '' }}>
                  <label class="custom-control-label small" for="perm{{ $perm->id }}">
                    {{ $perm->name }}
                  </label>
                </div>
              @empty
                <div class="text-muted small">No permissions in this module.</div>
              @endforelse
            </div>
          </div>
        </div>
      @endforeach
    </div>

  </div>

  <div class="card-footer bg-white border-top py-3 d-flex justify-content-end" style="gap:10px;">
    <a href="{{ route('company.settings.roles.index', ['company' => $company->uuid]) }}"
       class="btn btn-link text-secondary font-weight-bold">
      Cancel
    </a>

    <button type="submit" class="btn btn-primary px-4 shadow-sm font-weight-bold">
      <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Update Role' : 'Create Role' }}
    </button>
  </div>
</div>

@push('scripts')
<script>
$(function() {
  $('#btnSelectAll').on('click', function() {
    $('.perm-checkbox').prop('checked', true);
  });

  $('#btnClearAll').on('click', function() {
    $('.perm-checkbox').prop('checked', false);
  });

  $('.moduleToggle').on('click', function() {
    const moduleId = $(this).data('module');
    const $items = $('.perm-module-' + moduleId);

    const anyUnchecked = $items.toArray().some(el => !el.checked);
    $items.prop('checked', anyUnchecked);
  });
});
</script>
@endpush
