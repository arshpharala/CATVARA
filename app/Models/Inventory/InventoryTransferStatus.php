<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class InventoryTransferStatus extends Model
{
    protected $fillable = [
        'code',        // DRAFT, APPROVED, SHIPPED, RECEIVED, CLOSED
        'name',
        'is_final',
        'is_active',
    ];

    protected $casts = [
        'is_final' => 'boolean',
        'is_active' => 'boolean',
    ];

    /* ---------------- Relationships ---------------- */

    public function transfers()
    {
        return $this->hasMany(InventoryTransfer::class, 'status_id');
    }
}
