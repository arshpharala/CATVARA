<?php

namespace App\Models\Accounting;

use App\Models\Catalog\ProductVariant;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_variant_id',
        'product_name',
        'variant_description',
        'sku',
        'unit_price',
        'quantity',
        'line_total',
        'tax_amount',
        'discount_amount',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
