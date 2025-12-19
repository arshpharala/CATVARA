<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class InventoryTransferItem extends Model
{
    protected $fillable = [
        'inventory_transfer_id',
        'product_variant_id',
        'quantity',
        'received_quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'received_quantity' => 'decimal:6',
    ];

    public function transfer()
    {
        return $this->belongsTo(InventoryTransfer::class, 'inventory_transfer_id');
    }

    public function variant()
    {
        return $this->belongsTo(\App\Models\Catalog\ProductVariant::class, 'product_variant_id');
    }
}
