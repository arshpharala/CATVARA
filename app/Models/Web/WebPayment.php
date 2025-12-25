<?php

namespace App\Models\Web;

use Illuminate\Database\Eloquent\Model;

class WebPayment extends Model
{
    protected $fillable = [
        'web_order_id',
        'method',
        'status',
        'amount',
        'gateway_reference',
        'gateway_payload',
    ];

    protected $casts = [
        'gateway_payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(WebOrder::class, 'web_order_id');
    }
}
