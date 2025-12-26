<?php

namespace App\Models\Sales;

use App\Models\Accounting\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'customer_id',
        'status_id',
        'source',
        'source_id',
        'order_number',
        'currency_id',
        'payment_term_id',
        'payment_term_name',
        'payment_due_days',
        'due_date',
        'subtotal',
        'tax_total',
        'discount_total',
        'grand_total',
        'confirmed_at',
        'created_by'
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'due_date' => 'date',
        'subtotal' => 'decimal:6',
        'tax_total' => 'decimal:6',
        'grand_total' => 'decimal:6',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function status()
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
