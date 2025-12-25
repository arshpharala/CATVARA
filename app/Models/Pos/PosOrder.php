<?php

namespace App\Models\Pos;

use App\Models\Pos\PosOrderItem;
use Illuminate\Database\Eloquent\Model;

class PosOrder extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'store_id',
        'user_id',
        'order_number',
        'status_id',
        'currency_id',
        'subtotal',
        'tax_total',
        'discount_total',
        'shipping_amount',
        'grand_total',
        'exchange_rate',
        'completed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:6',
        'tax_total' => 'decimal:6',
        'discount_total' => 'decimal:6',
        'shipping_amount' => 'decimal:6',
        'grand_total' => 'decimal:6',
        'exchange_rate' => 'decimal:8',
        'completed_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(PosOrderItem::class);
    }

    public function status()
    {
        return $this->belongsTo(PosOrderStatus::class, 'status_id');
    }

    public function payments()
    {
        return $this->hasMany(PosPayment::class);
    }
}
