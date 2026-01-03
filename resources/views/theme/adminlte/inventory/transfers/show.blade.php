@extends('theme.adminlte.layouts.app')

@section('content-header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Transfer: {{ $transfer->reference }}</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ company_route('company.inventory.transfers.index') }}" class="btn btn-default">
          <i class="fas fa-arrow-left"></i> Back
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="row">
    <div class="col-md-8">
      {{-- TRANSFER INFO --}}
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Transfer Details</h3>
          <div class="card-tools">
            <span
              class="badge badge-{{ $transfer->status->code == 'CLOSED' ? 'success' : ($transfer->status->code == 'DRAFT' ? 'secondary' : 'info') }} badge-lg">
              {{ $transfer->status->name }}
            </span>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <dl>
                <dt>From Location</dt>
                <dd>{{ $transfer->fromLocation->locatable->name ?? '-' }}</dd>
                <dt>Created By</dt>
                <dd>{{ $transfer->creator->name ?? '-' }}</dd>
              </dl>
            </div>
            <div class="col-md-6">
              <dl>
                <dt>To Location</dt>
                <dd>{{ $transfer->toLocation->locatable->name ?? '-' }}</dd>
                <dt>Created At</dt>
                <dd>{{ $transfer->created_at->format('M d, Y H:i') }}</dd>
              </dl>
            </div>
          </div>
          @if ($transfer->notes)
            <div class="alert alert-info">
              <strong>Notes:</strong> {{ $transfer->notes }}
            </div>
          @endif
        </div>
      </div>

      {{-- ITEMS --}}
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Items</h3>
        </div>
        <div class="card-body p-0">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Variant (SKU)</th>
                <th>Product</th>
                <th class="text-right">Requested</th>
                <th class="text-right">Shipped</th>
                <th class="text-right">Received</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($transfer->items as $item)
                <tr>
                  <td>{{ $item->variant->sku ?? '-' }}</td>
                  <td>{{ $item->variant->product->name ?? '-' }}</td>
                  <td class="text-right">{{ (float) $item->quantity_requested }}</td>
                  <td class="text-right">{{ (float) $item->quantity_shipped }}</td>
                  <td class="text-right">{{ (float) $item->quantity_received }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      {{-- ACTIONS --}}
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Actions</h3>
        </div>
        <div class="card-body">
          @if ($transfer->status->code == 'DRAFT')
            <form action="{{ company_route('company.inventory.transfers.approve', ['transfer' => $transfer]) }}"
              method="POST">
              @csrf
              <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-check"></i> Approve Transfer
              </button>
            </form>
          @endif
          @if ($transfer->status->code == 'APPROVED')
            <form action="{{ company_route('company.inventory.transfers.ship', ['transfer' => $transfer]) }}"
              method="POST">
              @csrf
              <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-shipping-fast"></i> Ship Transfer
              </button>
            </form>
          @endif
          @if ($transfer->status->code == 'SHIPPED')
            <form action="{{ company_route('company.inventory.transfers.receive', ['transfer' => $transfer]) }}"
              method="POST">
              @csrf
              <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-box-open"></i> Receive Transfer
              </button>
            </form>
          @endif
          @if ($transfer->status->code == 'CLOSED' || $transfer->status->code == 'RECEIVED')
            <div class="alert alert-success mb-0">
              <i class="fas fa-check-circle"></i> Transfer Completed
            </div>
          @endif
        </div>
      </div>

      {{-- TIMELINE --}}
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Timeline</h3>
        </div>
        <div class="card-body">
          <div class="timeline timeline-inverse">
            <div class="time-label"><span class="bg-secondary">Created</span></div>
            <div>
              <i class="fas fa-file bg-secondary"></i>
              <div class="timeline-item">
                <span class="time"><i class="fas fa-clock"></i> {{ $transfer->created_at->format('H:i') }}</span>
                <h3 class="timeline-header">Transfer Created</h3>
              </div>
            </div>
            @if ($transfer->approved_at)
              <div class="time-label"><span class="bg-primary">Approved</span></div>
              <div>
                <i class="fas fa-check bg-primary"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> {{ $transfer->approved_at->format('H:i') }}</span>
                  <h3 class="timeline-header">Transfer Approved</h3>
                </div>
              </div>
            @endif
            @if ($transfer->shipped_at)
              <div class="time-label"><span class="bg-warning">Shipped</span></div>
              <div>
                <i class="fas fa-truck bg-warning"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> {{ $transfer->shipped_at->format('H:i') }}</span>
                  <h3 class="timeline-header">Items Shipped</h3>
                </div>
              </div>
            @endif
            @if ($transfer->received_at)
              <div class="time-label"><span class="bg-success">Received</span></div>
              <div>
                <i class="fas fa-box-open bg-success"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> {{ $transfer->received_at->format('H:i') }}</span>
                  <h3 class="timeline-header">Items Received</h3>
                </div>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
