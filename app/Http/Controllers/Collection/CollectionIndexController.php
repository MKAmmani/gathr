<?php

namespace App\Http\Controllers\Collection;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CollectionIndexController extends Controller
{
    /**
     * Display the collections index page.
     */
    public function index(Request $request): Response
    {
        $query = Collection::where('owner_id', Auth::id())
            ->with(['participants', 'payments'])
            ->orderBy('created_at', 'desc');

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by name if provided
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $collections = $query->get()->map(function ($collection) {
            $paidCount = $collection->participants->where('is_paid', true)->count();
            
            // Include both regular payments and guest payments (user_id is null)
            $raisedAmount = $collection->payments->sum('amount');

            $daysLeft = null;
            $isExpired = false;
            if ($collection->ends_at) {
                $daysLeft = now()->diffInDays($collection->ends_at, false);
                $isExpired = $collection->ends_at->isPast();
            }

            // Use model attributes for total raised and available balance
            $totalRaised = $collection->total_raised;
            $availableBalance = $collection->available_balance;

            // Count guest payments as "paid" contributors
            $guestPaymentCount = $collection->payments->whereNull('user_id')->count();
            $totalPaidCount = $paidCount + $guestPaymentCount;

            $progressPercentage = $collection->target_amount && $collection->target_amount > 0
                ? min(100, ($totalRaised / $collection->target_amount) * 100)
                : 0;

            return [
                'id' => $collection->id,
                'name' => $collection->name,
                'description' => $collection->description,
                'category' => $collection->category,
                'category_label' => $this->getCategoryLabel($collection->category),
                'icon' => $collection->icon,
                'contribution_amount' => $collection->contribution_amount,
                'target_amount' => $collection->target_amount,
                'participant_goal' => $collection->participant_goal,
                'status' => $collection->status,
                'type' => $collection->type,
                'starts_at' => $collection->starts_at?->format('d M Y'),
                'ends_at' => $collection->ends_at?->format('d M Y'),
                'created_at' => $collection->created_at->format('d M Y'),
                'paid_count' => $totalPaidCount,
                'raised_amount' => $totalRaised,
                'available_balance' => $collection->available_balance,
                'days_left' => $daysLeft,
                'is_expired' => $isExpired,
                'progress_percentage' => round($progressPercentage, 1),
                'slug' => strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $collection->name)),
            ];
        });

        // Get counts for filter tabs
        $allCount = Collection::where('owner_id', Auth::id())->count();
        $activeCount = Collection::where('owner_id', Auth::id())
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->count();
        $completedCount = Collection::where('owner_id', Auth::id())
            ->where('status', 'completed')
            ->count();
        $expiredCount = Collection::where('owner_id', Auth::id())
            ->where(function ($q) {
                $q->where('ends_at', '<', now())
                  ->orWhere('status', 'expired');
            })
            ->count();

        return Inertia::render('Collection/Index', [
            'user' => [
                'name' => Auth::user()->name,
            ],
            'collections' => $collections,
            'reputation' => $this->getUserReputation(),
            'filters' => [
                'all' => $allCount,
                'active' => $activeCount,
                'completed' => $completedCount,
                'expired' => $expiredCount,
            ],
        ]);
    }

    /**
     * Get user reputation based on collections
     */
    private function getUserReputation(): array
    {
        $user = Auth::user();
        $participationCount = $user->participations()->count() + $user->collections()->count();

        $tiers = [
            ['name' => 'Starter', 'level' => 1, 'min' => 0, 'next_at' => 2],
            ['name' => 'Rising Rep', 'level' => 2, 'min' => 2, 'next_at' => 4],
            ['name' => 'Campus Mogul', 'level' => 3, 'min' => 4, 'next_at' => null],
        ];

        $current = $tiers[0];
        foreach ($tiers as $tier) {
            if ($participationCount >= $tier['min']) {
                $current = $tier;
            }
        }

        return [
            'name' => $current['name'],
            'level' => $current['level'],
        ];
    }

    /**
     * Get category label.
     */
    private function getCategoryLabel(?string $category): string
    {
        return match($category) {
            'group' => 'Group Collection',
            'event' => 'Event Tickets',
            'business' => 'Campus Business',
            default => 'Group Collection',
        };
    }
}
