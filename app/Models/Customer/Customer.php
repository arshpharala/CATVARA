<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;
use App\Models\Customer\CustomerAddress;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'type',
        'display_name',
        'email',
        'phone',
        'legal_name',
        'tax_number',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function billingAddresses()
    {
        return $this->hasMany(CustomerAddress::class)->where('type', 'BILLING');
    }

    public function shippingAddresses()
    {
        return $this->hasMany(CustomerAddress::class)->where('type', 'SHIPPING');
    }
}
