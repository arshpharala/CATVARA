<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    protected $fillable = [
        'quote_id',
        'product_variant_id',
        'product_name',
        'variant_description',
        'unit_price',
        'quantity',
        'line_total',
        'tax_amount'
    ];
}
