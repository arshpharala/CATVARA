<footer class="main-footer">
  <div class="float-right d-none d-sm-inline">
    @php
      $company = active_company(); // helper
      $canSwitch = can_switch_company(); // helper
    @endphp

    @if($company && $canSwitch)
      <form action="{{ route('company.switch.reset') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-outline-secondary btn-sm">
          <i class="fas fa-random mr-1"></i> Change Company
        </button>
      </form>
    @endif
  </div>

  <strong class="mr-2">Company:</strong>

  @php
    $company = active_company();
  @endphp

  @if($company)
    <span class="badge badge-light border p-2">
      <i class="fas fa-building mr-1 text-muted"></i>
      {{ $company->name }} <span class="text-muted">({{ $company->code }})</span>
    </span>
  @else
    <span class="text-muted">No company selected</span>
  @endif
</footer>
