@php $isEdit = !empty($permission); @endphp

<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 border-bottom">
    <h3 class="card-title font-weight-bold text-dark mb-0">
      <i class="fas fa-key mr-2 text-primary"></i> Permission Details
    </h3>
  </div>

  <div class="card-body">
    <div class="row">
      <div class="col-md-4 form-group">
        <label class="small font-weight-bold text-muted text-uppercase">Module <span class="text-danger">*</span></label>
        <select name="module_id" class="form-control custom-select @error('module_id') is-invalid @enderror" required>
          <option value="">-- Select Module --</option>
          @foreach($modules as $m)
            <option value="{{ $m->id }}" {{ old('module_id', $permission->module_id ?? '') == $m->id ? 'selected' : '' }}>
              {{ $m->name }} ({{ $m->slug }})
            </option>
          @endforeach
        </select>
        @error('module_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-4 form-group">
        <label class="small font-weight-bold text-muted text-uppercase">Name <span class="text-danger">*</span></label>
        <input name="name" value="{{ old('name', $permission->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror"
               placeholder="e.g. View Orders" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-4 form-group">
        <label class="small font-weight-bold text-muted text-uppercase">Slug <span class="text-danger">*</span></label>
        <input name="slug" value="{{ old('slug', $permission->slug ?? '') }}"
               class="form-control @error('slug') is-invalid @enderror"
               placeholder="e.g. orders.view" required>
        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <small class="text-muted">Format: <b>module.action</b> (example: orders.view)</small>
      </div>
    </div>

    <div class="row mt-2">
      <div class="col-md-4 form-group mb-0">
        <label class="small font-weight-bold text-muted text-uppercase">Status</label>
        <div class="pt-2">
          <div class="custom-control custom-switch">
            <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="isActiveSwitch"
              {{ old('is_active', $permission->is_active ?? true) ? 'checked' : '' }}>
            <label class="custom-control-label font-weight-bold" for="isActiveSwitch">Active</label>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="card-footer bg-white border-top py-3 d-flex justify-content-end" style="gap:10px;">
    <a href="{{ route('permissions.index') }}" class="btn btn-link text-secondary font-weight-bold">Cancel</a>
    <button type="submit" class="btn btn-primary px-4 shadow-sm font-weight-bold">
      <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Update Permission' : 'Create Permission' }}
    </button>
  </div>
</div>
