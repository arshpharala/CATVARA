@php $isEdit = !empty($module); @endphp

<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 border-bottom">
    <h3 class="card-title font-weight-bold text-dark mb-0">
      <i class="fas fa-layer-group mr-2 text-primary"></i> Module Details
    </h3>
  </div>

  <div class="card-body">
    <div class="row">
      <div class="col-md-5 form-group">
        <label class="small font-weight-bold text-muted text-uppercase">Name <span class="text-danger">*</span></label>
        <input
          name="name"
          value="{{ old('name', $module->name ?? '') }}"
          class="form-control @error('name') is-invalid @enderror"
          placeholder="e.g. Inventory"
          required
        >
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-5 form-group">
        <label class="small font-weight-bold text-muted text-uppercase">Slug</label>
        <input
          name="slug"
          value="{{ old('slug', $module->slug ?? '') }}"
          class="form-control @error('slug') is-invalid @enderror"
          placeholder="e.g. inventory"
        >
        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <small class="text-muted">Leave blank to auto-generate from name.</small>
      </div>

      <div class="col-md-2 form-group">
        <label class="small font-weight-bold text-muted text-uppercase">Status</label>
        <div class="pt-2">
          <div class="custom-control custom-switch">
            <input
              type="checkbox"
              name="is_active"
              value="1"
              class="custom-control-input"
              id="isActiveSwitch"
              {{ old('is_active', $module->is_active ?? true) ? 'checked' : '' }}
            >
            <label class="custom-control-label font-weight-bold" for="isActiveSwitch">Active</label>
          </div>
        </div>
      </div>
    </div>

    <div class="alert alert-light border mt-2 mb-0">
      <div class="d-flex">
        <div class="mr-2 text-muted"><i class="fas fa-info-circle"></i></div>
        <div class="small text-muted">
          Modules are global. They are used to group permissions like <b>orders.view</b> under <b>Orders</b>.
        </div>
      </div>
    </div>
  </div>

  <div class="card-footer bg-white border-top py-3 d-flex justify-content-end" style="gap:10px;">
    <a href="{{ route('modules.index') }}" class="btn btn-link text-secondary font-weight-bold">Cancel</a>
    <button type="submit" class="btn btn-primary px-4 shadow-sm font-weight-bold">
      <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Update Module' : 'Create Module' }}
    </button>
  </div>
</div>
