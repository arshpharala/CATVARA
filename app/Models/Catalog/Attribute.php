<?php

namespace App\Models\Catalog;

use App\Models\Catalog\Category;
use App\Models\Catalog\AttributeValue;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'is_active',
    ];

    /* ================= Relations ================= */

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function categories()
    {
        return $this->belongsToMany(
            Category::class,
            'category_attributes'
        );
    }
}
