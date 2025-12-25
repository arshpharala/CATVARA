<?php

namespace App\Models\Web;

use Illuminate\Database\Eloquent\Model;

class WebOrderAddress extends Model
{
    protected $fillable = [
        'web_order_id',
        'type', // BILLING | SHIPPING
        'contact_name',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country_code',
    ];

    public function order()
    {
        return $this->belongsTo(WebOrder::class, 'web_order_id');
    }
}
