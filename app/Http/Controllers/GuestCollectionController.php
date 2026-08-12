<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionParticipation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuestCollectionController extends Controller
{
    /**
     * Display the guest collection page (accessible without auth).
     */
    public function show(string $slug): Response
    {
        // Parse slug to extract collection ID (format: slug-name-id)
        $parts = explode('-', $slug);
        $collectionId = end($parts);

        // Fetch collection with relationships
        $collection = Collection::with(['owner', 'participants.user', 'payments.user'])
            ->findOrFail($collectionId);

        // Get all participants
        $allParticipants = $collection->participants;

        // Calculate stats including guest payments
        $paidCount = $allParticipants->where('is_paid', true)->count();
        $guestPaymentCount = $collection->payments->whereNull('user_id')->count();
        $totalPaidCount = $paidCount + $guestPaymentCount;
        
        // Total "participants" includes both registered participants and guest payers
        $totalParticipants = $allParticipants->count() + $guestPaymentCount;
        
        // Use participant_goal if set, otherwise use totalParticipants
        $effectiveGoal = $collection->participant_goal ?: max($totalParticipants, 1);
        
        // Calculate raised amount including guest payments
        $participantPayments = $allParticipants->sum('amount_paid');
        $guestPayments = $collection->payments->whereNull('user_id')->sum('amount');
        $raisedAmount = $participantPayments + $guestPayments;

        // Calculate days left
        $daysLeft = 0;
        $deadlineFormatted = null;
        if ($collection->ends_at) {
            $daysLeft = (int) round(max(0, now()->diffInDays($collection->ends_at, false)));
            $deadlineFormatted = $collection->ends_at->format('l M j ga');
        }

        // Calculate progress percentage
        $targetAmount = $collection->target_amount ?? 0;
        $progressPercentage = $targetAmount > 0
            ? min(100, ($raisedAmount / $targetAmount) * 100)
            : 0;

        // Get recent contributors (last 4 paid participants including guest payments)
        $recentContributors = $allParticipants
            ->where('is_paid', true)
            ->filter(fn($p) => $p->user !== null)
            ->take(4)
            ->map(function ($participant) {
                $name = $participant->user->name ?? 'Unknown';
                $initials = strtoupper(substr($name, 0, 2));
                return [
                    'id' => $participant->id,
                    'name' => $name,
                    'initials' => $initials,
                ];
            });
        
        // Add guest payment contributors
        $guestPayers = $collection->payments()
            ->whereNull('user_id')
            ->orderByDesc('paid_at')
            ->limit(4)
            ->get()
            ->map(function ($payment) {
                $name = $payment->customer_name ?? 'Anonymous';
                $initials = strtoupper(substr($name, 0, 2));
                return [
                    'id' => 'guest_' . $payment->id,
                    'name' => $name,
                    'initials' => $initials ?: '?',
                ];
            });
        
        // Merge and limit to 4
        $allContributors = $recentContributors->concat($guestPayers)->take(4);

        // Get owner initials
        $ownerName = $collection->owner->name ?? 'Unknown';
        $ownerInitials = strtoupper(substr($ownerName, 0, 2));

        // Calculate half payment amount
        $halfPaymentAmount = $collection->contribution_amount > 0
            ? ceil($collection->contribution_amount / 2)
            : 0;

        // Prepare description
        $description = $collection->description;
        if (!$description) {
            $confirmedCount = $totalParticipants > 0 ? $totalParticipants : ($collection->participant_goal ?? 0);
            $description = "This collection is for the {$collection->name} organised by {$ownerName} - confirmed for {$confirmedCount} people";
        }

        return Inertia::render('guest/Index', [
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name,
                'slug' => $slug,
                'description' => $description,
                'category_label' => $this->getCategoryLabel($collection->category ?? 'group'),
                'icon' => $collection->icon ?? 'group',
                'contribution_amount' => $collection->contribution_amount,
                'target_amount' => $targetAmount,
                'participant_goal' => $collection->participant_goal,
                'ends_at' => $deadlineFormatted,
                'is_expired' => $collection->isExpired(),
                'allow_half_payment' => $collection->allow_half_payment,
                'half_payment_amount' => $halfPaymentAmount,
                'status' => $collection->status,
            ],
            'owner' => [
                'id' => $collection->owner->id,
                'name' => $ownerName,
                'initials' => $ownerInitials,
                'institution' => $collection->owner->institution,
                'is_verified' => true, // Can be enhanced with actual verification logic
                'reputation' => [
                    'name' => 'Rising Rep',
                    'level' => 2,
                ],
            ],
            'stats' => [
                'paid_count' => $totalPaidCount,
                'total_participants' => $totalParticipants,
                'raised_amount' => $raisedAmount,
                'days_left' => $daysLeft,
                'progress_percentage' => round($progressPercentage, 1),
            ],
            'recent_contributors' => $allContributors,
            'appUrl' => config('app.url'),
        ]);
    }

    /**
     * Get category label.
     */
    private function getCategoryLabel(string $category): string
    {
        return match($category) {
            'group' => 'Group Collection',
            'event' => 'Event Tickets',
            'business' => 'Campus Business',
            default => 'Group Collection',
        };
    }
}
