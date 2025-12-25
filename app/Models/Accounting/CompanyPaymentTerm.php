<?php

namespace App\Models\Accounting;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Model;

class CompanyPaymentTerm extends Model
{
    protected $fillable = [
        'company_id',
        'payment_term_id',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class);
    }
}
