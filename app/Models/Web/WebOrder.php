<?php

namespace App\Models\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Accounting\Payment;
use App\Models\Customer\Customer;
use App\Models\Web\WebOrderItem;
use App\Models\Web\WebOrderStatus;
use App\Models\Web\WebOrderAddress;

class WebOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'customer_id',
        'status_id',
        'order_number',
        'currency_id',

        // payment term snapshot
        'payment_term_id',
        'payment_term_code',
        'payment_term_name',
        'payment_due_days',
        'due_date',
        'is_credit_sale',

        'subtotal',
        'tax_total',
        'shipping_amount',
        'grand_total',
        'placed_at',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
        'due_date' => 'datetime',
        'is_credit_sale' => 'boolean',
        'payment_due_days' => 'integer',
    ];

    public function status()
    {
        return $this->belongsTo(WebOrderStatus::class, 'status_id');
    }

    public function items()
    {
        return $this->hasMany(WebOrderItem::class);
    }

    public function addresses()
    {
        return $this->hasMany(WebOrderAddress::class);
    }

    public function billingAddress()
    {
        return $this->hasOne(WebOrderAddress::class)->where('type', 'BILLING');
    }

    public function shippingAddress()
    {
        return $this->hasOne(WebOrderAddress::class)->where('type', 'SHIPPING');
    }

    /**
     * ✅ Unified payments
     */
    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function isFinal(): bool
    {
        return (bool) optional($this->status)->is_final;
    }
}
