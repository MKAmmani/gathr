<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestPayment extends Model
{
    protected $fillable = [
        'collection_id',
        'transaction_reference',
        'payment_reference',
        'customer_name',
        'customer_email',
        'amount',
        'is_anonymous',
        'payment_type',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }
}
