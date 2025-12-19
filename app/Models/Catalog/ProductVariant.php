<?php

namespace App\Models\Catalog;

use App\Models\Catalog\Product;
use App\Models\Common\Attachment;
use App\Models\Catalog\AttributeValue;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'product_id',
        'sku',
        'barcode',
        'is_active',
    ];

    /* ================= Relations ================= */

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_variant_attribute_values'
        );
    }

    public function attachments()
    {
        return $this->morphMany(
            Attachment::class,
            'attachable'
        );
    }
}
