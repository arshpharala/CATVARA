<?php

namespace App\Models\Web;

use App\Models\Web\WebPayment;
use App\Models\Web\WebOrderItem;
use App\Models\Customer\Customer;
use App\Models\Web\WebOrderStatus;
use App\Models\Web\WebOrderAddress;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'subtotal',
        'tax_total',
        'shipping_amount',
        'grand_total',
        'placed_at',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
    ];

    /* ========================
     | Relationships
     ======================== */

    public function status()
    {
        return $this->belongsTo(WebOrderStatus::class, 'status_id');
    }

    public function items()
    {
        return $this->hasMany(WebOrderItem::class, 'web_order_id');
    }

    public function addresses()
    {
        return $this->hasMany(WebOrderAddress::class, 'web_order_id');
    }

    public function billingAddress()
    {
        return $this->hasOne(WebOrderAddress::class, 'web_order_id')
            ->where('type', 'BILLING');
    }

    public function shippingAddress()
    {
        return $this->hasOne(WebOrderAddress::class, 'web_order_id')
            ->where('type', 'SHIPPING');
    }

    public function payments()
    {
        return $this->hasMany(WebPayment::class, 'web_order_id');
    }

    /* ========================
     | Helpers
     ======================== */

    public function isFinal(): bool
    {
        return (bool) optional($this->status)->is_final;
    }
}
