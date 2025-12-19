<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'code',
        'phone',
        'address',
        'is_active',
    ];

    public function inventoryLocation()
    {
        return $this->morphOne(
            InventoryLocation::class,
            'locatable'
        );
    }
}
