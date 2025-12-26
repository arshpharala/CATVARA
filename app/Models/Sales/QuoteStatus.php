<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

class QuoteStatus extends Model
{
    protected $fillable = ['code', 'name', 'is_final', 'is_active'];
}
