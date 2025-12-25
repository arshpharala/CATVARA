<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;

class PriceChannel extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];
}
