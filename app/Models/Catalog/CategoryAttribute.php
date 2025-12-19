<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;

class CategoryAttribute extends Model
{
    protected $fillable = [
        'category_id',
        'attribute_id',
    ];

    public $timestamps = true;
}
