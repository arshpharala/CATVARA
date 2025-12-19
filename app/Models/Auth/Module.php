<?php

namespace App\Models\Auth;

use App\Models\Auth\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
