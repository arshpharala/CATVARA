<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'payment_method_id',

        // SOURCE reference only (NOT accounting logic)
        'payable_type',
        'payable_id',

        'payment_currency_id',
        'base_currency_id',

        'amount',
        'exchange_rate',
        'base_amount',
        'fx_difference',

        'direction',
        'status',

        'source',
        'document_no',
        'reference',
        'gateway_reference',
        'gateway_payload',

        'idempotency_key',
        'paid_at',
    ];

    protected $casts = [
        'amount'        => 'decimal:6',
        'base_amount'   => 'decimal:6',
        'exchange_rate' => 'decimal:8',
        'fx_difference' => 'decimal:6',
        'gateway_payload' => 'array',
        'paid_at' => 'datetime',
    ];

    /* ==========================
     | Relationships
     ========================== */

    /**
     * Original source document (POS / WEB / API)
     * NOT used for accounting logic
     */
    public function payable()
    {
        return $this->morphTo();
    }

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    /**
     * TRUE accounting linkage
     */
    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /* ==========================
     | Helpers (Enterprise-grade)
     ========================== */

    public function allocatedAmount(): string
    {
        return (string) $this->allocations()->sum('allocated_amount');
    }

    public function unallocatedAmount(): string
    {
        return bcsub(
            (string) $this->amount,
            (string) $this->allocatedAmount(),
            6
        );
    }

    public function isFullyAllocated(): bool
    {
        return bccomp(
            (string) $this->allocatedAmount(),
            (string) $this->amount,
            6
        ) === 0;
    }
}
