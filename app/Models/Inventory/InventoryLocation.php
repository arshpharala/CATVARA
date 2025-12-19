<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class InventoryLocation extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'type',
        'is_active',
    ];

    public function locatable()
    {
        return $this->morphTo();
    }
}
