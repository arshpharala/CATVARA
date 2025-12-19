<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryReason extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'is_increase',
        'is_active',
    ];

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
