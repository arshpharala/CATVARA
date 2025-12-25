<?php

namespace App\Models\Accounting;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Model;

class PaymentTerm extends Model
{
    protected $fillable = [
        'code',
        'name',
        'due_days',
        'is_active',
    ];

    protected $casts = [
        'due_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function companies()
    {
        return $this->belongsToMany(
            Company::class,
            'company_payment_terms'
        )->withPivot('is_default');
    }
}
