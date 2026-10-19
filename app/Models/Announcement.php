<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'subject',
        'badge',
        'message',
        'action_text',
        'action_url',
        'recipients_count',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'recipients_count' => 'integer',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
