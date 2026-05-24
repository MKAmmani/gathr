<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'category',
        'icon',
        'contribution_amount',
        'target_amount',
        'participant_goal',
        'status',
        'type',
        'starts_at',
        'ends_at',
        'allow_half_payment',
        'anonymous_payments',
        'organizer_pay_charges',
        'allow_custom_amount',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'allow_half_payment' => 'boolean',
        'anonymous_payments' => 'boolean',
        'organizer_pay_charges' => 'boolean',
        'allow_custom_amount' => 'boolean',
    ];

    public function getTotalRaisedAttribute(): float
    {
        return (float) round($this->payments()->sum('amount') ?? 0, 0);
    }

    public function getAvailableBalanceAttribute(): float
    {
        $totalRaised = $this->total_raised;
        // Only subtract completed withdrawals from the available balance.
        // Pending/Processing withdrawals are still "in the system" and shown separately in the UI.
        $totalWithdrawn = (float) round($this->withdrawals()
            ->where('status', 'completed')
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(amount, 0) + COALESCE(fees, 0)')), 0);

        return (float) max(0, $totalRaised - $totalWithdrawn);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CollectionParticipation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CollectionPayment::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function guestPayments(): HasMany
    {
        return $this->hasMany(GuestPayment::class);
    }
}
