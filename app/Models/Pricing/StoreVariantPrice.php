<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;

class StoreVariantPrice extends Model
{
    protected $fillable = [
        'store_id',
        'variant_price_id',
        'price_override',
    ];

    protected $casts = [
        'price_override' => 'decimal:6',
    ];
}
