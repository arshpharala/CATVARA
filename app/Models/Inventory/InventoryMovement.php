<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'inventory_location_id',
        'product_variant_id',
        'inventory_reason_id',
        'quantity',
        'unit_cost',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'performed_by',
        'occurred_at',
        'posted_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_cost' => 'decimal:6',
        'occurred_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function reason()
    {
        return $this->belongsTo(InventoryReason::class, 'inventory_reason_id');
    }

    public function location()
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function variant()
    {
        return $this->belongsTo(\App\Models\Catalog\ProductVariant::class, 'product_variant_id');
    }

    public function performer()
    {
        return $this->belongsTo(\App\Models\User::class, 'performed_by');
    }
}
