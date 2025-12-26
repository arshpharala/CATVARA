<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_variant_id',
        'product_name',
        'variant_description',
        'unit_price',
        'quantity',
        'fulfilled_quantity',
        'line_total',
        'tax_amount'
    ];
}
