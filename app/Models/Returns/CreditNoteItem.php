<?php

namespace App\Models\Returns;

use Illuminate\Database\Eloquent\Model;

class CreditNoteItem extends Model
{
    protected $fillable = [
        'credit_note_id',
        'product_variant_id',
        'unit_price',
        'quantity',
        'line_total',
        'tax_amount',
        'source_item_id',
        'source_item_type',
    ];

    protected $casts = [
        'unit_price' => 'decimal:6',
        'line_total' => 'decimal:6',
        'tax_amount' => 'decimal:6',
    ];

    public function sourceItem()
    {
        return $this->morphTo(__FUNCTION__, 'source_item_type', 'source_item_id');
    }
}
