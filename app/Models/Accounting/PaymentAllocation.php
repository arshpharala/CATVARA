<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentAllocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'payment_id',
        'allocatable_type',
        'allocatable_id',
        'payment_currency_id',
        'base_currency_id',
        'allocated_amount',
        'exchange_rate',
        'base_allocated_amount',
        'reason',
        'allocated_at',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:6',
        'base_allocated_amount' => 'decimal:6',
        'exchange_rate' => 'decimal:8',
        'allocated_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function allocatable()
    {
        return $this->morphTo();
    }
}
