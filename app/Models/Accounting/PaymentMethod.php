<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'code',
        'name',
        'type',
        'is_active',
        'allow_refund',
        'requires_reference',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_refund' => 'boolean',
        'requires_reference' => 'boolean',
    ];
}
