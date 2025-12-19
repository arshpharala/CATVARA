<?php

namespace App\Models\Company;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyStatus extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function companies()
    {
        return $this->hasMany(Company::class);
    }
}
