<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'base_currency_id',
        'target_currency_id',
        'rate',
        'effective_date',
        'source',
    ];
}
