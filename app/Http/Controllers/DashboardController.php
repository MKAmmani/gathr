<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionParticipation;
use App\Models\CollectionPayment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        
        // Get collections the user participates in
        $participationCollectionIds = $user->participations()->pluck('collection_id');
        
        // Get collections the user owns
        $ownedCollectionIds = $user->collections()->pluck('id');
        
        // Combine both sets of collection IDs
        $collectionIds = $participationCollectionIds->merge($ownedCollectionIds)->unique();
        
        $participationCount = $user->participations()->count() + $user->collections()->count();

        // Query for ALL collections (both owned and participated)
        $collectionsQuery = Collection::query()
            ->whereIn('id', $collectionIds)
            ->withCount('participants')
            ->withCount([
                'participants as paid_count' => fn ($query) => $query
                    ->where(function ($subQuery) {
                        $subQuery
                            ->where('is_paid', true)
                            ->orWhere('amount_paid', '>', 0);
                    }),
            ])
            ->withSum('participants as amount_raised', 'amount_paid')
            ->orderByDesc('created_at');

        $collections = $collectionsQuery
            ->get()
            ->map(function (Collection $collection) {
                $goal = $collection->participant_goal ?? 0;
                $paidCount = (int) ($collection->paid_count ?? 0);
                $totalParticipants = (int) ($collection->participants_count ?? 0);

                // Calculate progress based on participant_goal if set, otherwise based on actual participants
                if ($goal > 0) {
                    $progress = (int) round(($paidCount / $goal) * 100);
                } elseif ($totalParticipants > 0) {
                    $progress = (int) round(($paidCount / $totalParticipants) * 100);
                } else {
                    $progress = 0;
                }

                // Calculate total amount raised and available balance using model attributes
                $totalAmountRaised = $collection->total_raised;
                $availableBalance = $collection->available_balance;

                // Count guest payments as paid contributors
                $guestPaymentCount = (int) $collection->payments()
                    ->whereNull('user_id')
                    ->count();
                $totalPaidCount = $paidCount + $guestPaymentCount;

                // Determine collection status based on deadline and payment completion
                $isExpired = $collection->ends_at && $collection->ends_at->isPast();
                $allParticipantsPaid = $totalParticipants > 0 && $totalPaidCount >= $totalParticipants;
                
                // Status logic:
                // 1. If deadline passed and not all paid -> expired
                // 2. If all participants paid -> completed
                // 3. Otherwise -> active
                if ($isExpired && !$allParticipantsPaid) {
                    $status = 'expired';
                } elseif ($allParticipantsPaid) {
                    $status = 'completed';
                } else {
                    $status = 'active';
                }

                // Calculate days left
                $daysLeft = $collection->ends_at ? now()->diffInDays($collection->ends_at, false) : null;
                $daysLeft = max(0, $daysLeft ?? 0);

                return [
                    'id' => $collection->id,
                    'name' => $collection->name,
                    'category' => $collection->category,
                    'icon' => $collection->icon,
                    'created_at' => optional($collection->created_at)->toDateString(),
                    'contribution_amount' => $collection->contribution_amount,
                    'participant_goal' => $collection->participant_goal,
                    'participants_count' => $totalParticipants,
                    'paid_count' => $totalPaidCount,
                    'amount_raised' => $totalAmountRaised,
                    'available_balance' => $availableBalance,
                    'days_left' => $daysLeft,
                    'status' => $status,
                    'type' => $collection->type,
                    'progress_percent' => $progress,
                ];
            });

        $totalBalance = Collection::where('owner_id', $user->id)
            ->get()
            ->sum('available_balance');
        
        $totalPaid = CollectionParticipation::whereIn('collection_id', $collectionIds)
            ->where('is_paid', true)
            ->count();
        $totalUnpaid = CollectionParticipation::whereIn('collection_id', $collectionIds)
            ->where('is_paid', false)
            ->count();

        // Recent activity from owned collections and participated collections
        // Include both regular payments and guest payments
        $recentActivity = CollectionPayment::query()
            ->whereIn('collection_id', $collectionIds)
            ->with('user:id,name')
            ->orderByDesc('paid_at')
            ->take(3)
            ->get()
            ->map(function (CollectionPayment $payment) {
                // For guest payments (user_id is null), use customer_name if available
                $isGuestPayment = $payment->user_id === null;
                $payerName = $isGuestPayment 
                    ? ($payment->customer_name ?? 'Anonymous')
                    : ($payment->user?->name ?? 'Anonymous');
                
                return [
                    'id' => $payment->id,
                    'payer_name' => $payerName,
                    'amount' => (int) $payment->amount,
                    'note' => $payment->note,
                    'paid_at' => optional($payment->paid_at)->toDateTimeString(),
                    'is_guest' => $payment->user_id === null,
                ];
            });

        $reputation = $this->reputationFromParticipations($participationCount);

        return Inertia::render('Dashboard', [
            'user' => [
                'name' => $user->name,
            ],
            'stats' => [
                'totalBalance' => $totalBalance,
            ],
            'reputation' => $reputation,
            'collections' => $collections,
            'recentActivity' => $recentActivity,
        ]);
    }

    private function reputationFromParticipations(int $count): array
    {
        $tiers = [
            [
                'name' => 'Starter',
                'level' => 1,
                'min' => 0,
                'next_name' => 'Rising Rep',
                'next_at' => 2,
            ],
            [
                'name' => 'Rising Rep',
                'level' => 2,
                'min' => 2,
                'next_name' => 'Campus Mogul',
                'next_at' => 4,
            ],
            [
                'name' => 'Campus Mogul',
                'level' => 3,
                'min' => 4,
                'next_name' => null,
                'next_at' => null,
            ],
        ];

        $current = $tiers[0];
        foreach ($tiers as $tier) {
            if ($count >= $tier['min']) {
                $current = $tier;
            }
        }

        $nextAt = $current['next_at'];
        $progress = $nextAt
            ? (int) round((($count - $current['min']) / max(1, $nextAt - $current['min'])) * 100)
            : 100;
        $remaining = $nextAt ? max(0, $nextAt - $count) : 0;

        return [
            'name' => $current['name'],
            'level' => $current['level'],
            'progress' => max(0, min(100, $progress)),
            'next_name' => $current['next_name'],
            'remaining' => $remaining,
        ];
    }
}
