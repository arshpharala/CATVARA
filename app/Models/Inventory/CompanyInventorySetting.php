<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class CompanyInventorySetting extends Model
{
    protected $fillable = [
        'company_id',
        'allow_negative_stock',
        'block_sale_if_no_stock',
        'require_transfer_approval',
        'auto_receive_transfer',
        'allow_partial_transfer_receive',
        'default_inventory_location_id',
    ];

    public function defaultLocation()
    {
        return $this->belongsTo(
            InventoryLocation::class,
            'default_inventory_location_id'
        );
    }
}
