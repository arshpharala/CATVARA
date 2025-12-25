<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'store_id',
        'customer_id',
        'status_id',
        'invoice_number',
        'source_type',
        'source_id',
        'currency_id',
        'payment_term_id',
        'payment_due_days',
        'due_date',
        'subtotal',
        'tax_total',
        'discount_total',
        'shipping_amount',
        'grand_total',
        'exchange_rate',
        'issued_at',
        'voided_at',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'issued_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function status()
    {
        return $this->belongsTo(InvoiceStatus::class, 'status_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function addresses()
    {
        return $this->hasMany(InvoiceAddress::class, 'invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer\Customer::class, 'customer_id');
    }
}
