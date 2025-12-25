<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Model;

class PosOrderItem extends Model
{
    protected $fillable = [
        'pos_order_id',
        'product_variant_id',
        'unit_price',
        'quantity',
        'line_total',
        'tax_amount',
    ];

    protected $casts = [
        'unit_price' => 'decimal:6',
        'line_total' => 'decimal:6',
        'tax_amount' => 'decimal:6',
    ];
}
