<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class InvoiceStatus extends Model
{
    protected $fillable = ['code', 'name', 'is_final', 'is_active'];

    protected $casts = [
        'is_final' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'status_id');
    }
}
