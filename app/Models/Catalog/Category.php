<?php

namespace App\Models\Catalog;

use App\Models\Catalog\Product;
use App\Models\Catalog\Attribute;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'slug',
        'is_active',
    ];

    /* ================= Relations ================= */

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function attributes()
    {
        return $this->belongsToMany(
            Attribute::class,
            'category_attributes'
        );
    }
}
