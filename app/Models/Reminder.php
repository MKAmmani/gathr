<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    protected $fillable = [
        'collection_id',
        'email',
        'reminder_type',
        'scheduled_at',
        'is_sent',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }
}
