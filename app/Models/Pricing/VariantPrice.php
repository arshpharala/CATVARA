<?php

namespace App\Models\Pricing;

use App\Models\Pricing\Currency;
use App\Models\Pricing\PriceChannel;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pricing\StoreVariantPrice;

class VariantPrice extends Model
{
    protected $fillable = [
        'company_id',
        'product_variant_id',
        'price_channel_id',
        'currency_id',
        'country_code',
        'price',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:6',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function priceChannel()
    {
        return $this->belongsTo(
            PriceChannel::class,
            'price_channel_id'
        );
    }

    public function currency()
    {
        return $this->belongsTo(
            Currency::class,
            'currency_id'
        );
    }

    public function storeOverrides()
    {
        return $this->hasMany(
            StoreVariantPrice::class,
            'variant_price_id'
        );
    }
}
