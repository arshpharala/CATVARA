<?php

namespace App\Models\Sales;

use App\Models\Sales\QuoteItem;
use App\Models\Sales\QuoteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'customer_id',
        'status_id',
        'quote_number',
        'currency_id',
        'payment_term_id',
        'payment_term_name',
        'payment_due_days',
        'subtotal',
        'tax_total',
        'discount_total',
        'grand_total',
        'valid_until',
        'sent_at',
        'accepted_at',
        'created_by'
    ];

    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }
    public function status()
    {
        return $this->belongsTo(QuoteStatus::class);
    }
}
