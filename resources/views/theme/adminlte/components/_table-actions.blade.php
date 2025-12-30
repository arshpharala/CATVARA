<div class="d-flex gap-2">

  @if (!empty($showUrl))
    <a href="{{ $showUrl }}" class="btn btn-sm btn-secondary">View</a>
  @endif

  @if (empty($row->deleted_at))
    @if (!empty($editUrl))
      @if (!empty($editSidebar))
        <button data-url="{{ $editUrl }}" type="button" class="btn btn-sm btn-secondary"
          onclick="getAside()">Edit</button>
      @else
        <a href="{{ $editUrl }}" class="btn btn-sm btn-secondary">Edit</a>
      @endif

    @endif
    @if (!empty($deleteUrl))
      <button type="button" class="btn btn-sm btn-danger btn-delete" data-url="{{ $deleteUrl }}">Delete</button>
    @endif
  @elseif(!empty($restoreUrl))
    <button data-url="{{ $restoreUrl }}" class="btn btn-sm btn-success btn-delete">Restore</button>
  @endif

</div>
