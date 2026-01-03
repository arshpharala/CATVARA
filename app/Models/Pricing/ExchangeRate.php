<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'company_id',
        'base_currency_id',
        'target_currency_id',
        'rate',
        'effective_date',
        'source',
    ];

    public function company()
    {
        return $this->belongsTo(\App\Models\Company\Company::class);
    }

    public function baseCurrency()
    {
        return $this->belongsTo(Currency::class, 'base_currency_id');
    }

    public function targetCurrency()
    {
        return $this->belongsTo(Currency::class, 'target_currency_id');
    }
}
