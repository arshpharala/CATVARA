<?php

namespace App\Models\Web;

use Illuminate\Database\Eloquent\Model;

class WebOrderItem extends Model
{
    protected $fillable = [
        'web_order_id',
        'product_variant_id',
        'product_name',
        'variant_description',
        'unit_price',
        'quantity',
        'line_total',
        'tax_amount',
    ];

    /* ========================
     | Relationships
     ======================== */

    public function order()
    {
        return $this->belongsTo(WebOrder::class, 'web_order_id');
    }
}
