<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class InventoryBalance extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'inventory_location_id',
        'product_variant_id',
        'quantity',
        'last_movement_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'last_movement_at' => 'datetime',
    ];

    public function location()
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function variant()
    {
        return $this->belongsTo(\App\Models\Catalog\ProductVariant::class, 'product_variant_id');
    }
}
