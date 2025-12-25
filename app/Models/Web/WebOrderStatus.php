<?php

namespace App\Models\Web;

use Illuminate\Database\Eloquent\Model;

class WebOrderStatus extends Model
{
    protected $table = 'web_order_statuses';

    protected $fillable = [
        'code',
        'name',
        'is_final',
        'is_active',
    ];

    protected $casts = [
        'is_final' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(WebOrder::class, 'status_id');
    }
}
