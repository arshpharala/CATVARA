<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class InventoryTransfer extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'from_location_id',
        'to_location_id',
        'status_id',
        'transfer_no',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'shipped_at',
        'received_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function fromLocation()
    {
        return $this->belongsTo(InventoryLocation::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(InventoryLocation::class, 'to_location_id');
    }

    public function items()
    {
        return $this->hasMany(InventoryTransferItem::class, 'inventory_transfer_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function status()
    {
        return $this->belongsTo(InventoryTransferStatus::class, 'status_id');
    }
}
