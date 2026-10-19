<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $activeUsersCount = User::whereNotNull('email_verified_at')->count();
        $totalUsersCount = User::count();

        $activeCollectionsQuery = Collection::query()
            ->where('status', 'active')
            ->with(['owner:id,name,email,nickname', 'payments', 'withdrawals'])
            ->withCount('participants')
            ->latest();

        $activeCollections = $activeCollectionsQuery->get();

        $activeCollectionsCount = $activeCollections->count();

        $totalActiveBalance = (float) $activeCollections->sum(function (Collection $collection) {
            return $collection->available_balance;
        });

        $totalActiveRaised = (float) $activeCollections->sum(function (Collection $collection) {
            return $collection->total_raised;
        });

        $recentAnnouncements = Announcement::with('admin:id,name,email')
            ->latest()
            ->take(3)
            ->get();

        $collectionsData = $activeCollections->take(10)->map(function (Collection $collection) {
            return [
                'id' => $collection->id,
                'name' => $collection->name,
                'category' => $collection->category,
                'icon' => $collection->icon,
                'owner_name' => $collection->owner?->name ?? 'Unknown',
                'owner_email' => $collection->owner?->email ?? 'N/A',
                'target_amount' => (float) ($collection->target_amount ?? 0),
                'total_raised' => (float) $collection->total_raised,
                'available_balance' => (float) $collection->available_balance,
                'participants_count' => (int) $collection->participants_count,
                'ends_at' => $collection->ends_at?->toDateString(),
                'created_at' => $collection->created_at?->toDateString(),
            ];
        });

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'active_users_count' => $activeUsersCount,
                'total_users_count' => $totalUsersCount,
                'active_collections_count' => $activeCollectionsCount,
                'total_active_balance' => $totalActiveBalance,
                'total_active_raised' => $totalActiveRaised,
            ],
            'recentCollections' => $collectionsData,
            'recentAnnouncements' => $recentAnnouncements,
        ]);
    }
}
