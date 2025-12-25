<?php

namespace App\Models\Returns;

use App\Models\Returns\CreditNoteItem;
use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'store_id',
        'creditable_id',
        'creditable_type',
        'user_id',
        'credit_number',
        'status',
        'currency_id',
        'subtotal',
        'tax_total',
        'discount_total',
        'shipping_refund',
        'grand_total',
        'reason',
        'issued_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:6',
        'tax_total' => 'decimal:6',
        'discount_total' => 'decimal:6',
        'shipping_refund' => 'decimal:6',
        'grand_total' => 'decimal:6',
        'issued_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    public function creditable()
    {
        return $this->morphTo();
    }
}
