<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Model;

class PosOrderStatus extends Model
{
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

    /**
     * Orders under this status
     */
    public function orders()
    {
        return $this->hasMany(PosOrder::class, 'status_id');
    }
}
