<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'attachable_type',
        'attachable_id',
        'disk',
        'path',
        'file_name',
        'mime_type',
        'size',
        'is_primary',
        'sort_order',
    ];

    public function attachable()
    {
        return $this->morphTo();
    }
}
