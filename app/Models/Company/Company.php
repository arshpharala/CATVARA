<?php

namespace App\Models\Company;

use App\Models\User;
use App\Models\Auth\Role;
use Illuminate\Support\Str;
use App\Models\Company\CompanyDetail;
use App\Models\Company\CompanyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid ??= (string) Str::uuid();
        });
    }

    public function status()
    {
        return $this->belongsTo(CompanyStatus::class, 'company_status_id');
    }

    public function detail()
    {
        return $this->hasOne(CompanyDetail::class);
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'company_user'
        )->withPivot(['is_owner', 'is_active'])->withTimestamps();
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    public function baseCurrency()
    {
        return $this->belongsTo(\App\Models\Pricing\Currency::class, 'base_currency_id');
    }

    public function exchangeRates()
    {
        return $this->hasMany(\App\Models\Pricing\ExchangeRate::class);
    }

    public function paymentTerms()
    {
        return $this->belongsToMany(
            \App\Models\Accounting\PaymentTerm::class,
            'company_payment_terms'
        )->withPivot('is_default')->withTimestamps();
    }
}
