<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    protected $fillable = ['code', 'name', 'is_final', 'is_active'];
}
