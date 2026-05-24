<?php

namespace App\Http\Controllers\Collection;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    /**
     * Display the collection creation page 1.
     */
    public function createPage1(): Response
    {
        return Inertia::render('Collection/page1', [
            'user' => [
                'name' => Auth::user()->name,
            ],
            'reputation' => $this->getUserReputation(),
        ]);
    }

    /**
     * Display the collection creation page 2.
     */
    public function createPage2(Request $request): Response
    {
        $data = $request->session()->get('collection_data', []);

        return Inertia::render('Collection/Page2', [
            'user' => [
                'name' => Auth::user()->name,
            ],
            'reputation' => $this->getUserReputation(),
            'collectionData' => $data,
        ]);
    }

    /**
     * Store collection data from page 1 in session.
     */
    public function storePage1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:group,event,business',
        ]);

        $request->session()->put('collection_data', array_merge(
            $request->session()->get('collection_data', []),
            $validated
        ));

        return redirect()->route('collections.create.page2');
    }

    /**
     * Store collection data from page 2 in session and create collection.
     */
    public function store(Request $request): RedirectResponse
    {
        $page1Data = $request->session()->get('collection_data', []);

        // Validate that page 1 data exists
        if (empty($page1Data['name'])) {
            return redirect()->route('collections.create.page1')
                ->with('error', 'Please complete the collection details first.');
        }

        // Handle boolean fields - default to false if not present
        $request->merge([
            'allow_half_payment' => $request->boolean('allow_half_payment'),
            'anonymous_payments' => $request->boolean('anonymous_payments'),
            'organizer_pay_charges' => $request->boolean('organizer_pay_charges'),
            'allow_custom_amount' => $request->boolean('allow_custom_amount'),
        ]);

        $validated = $request->validate([
            'contribution_amount' => 'nullable|integer|min:0',
            'participant_goal' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'allow_half_payment' => 'boolean',
            'anonymous_payments' => 'boolean',
            'organizer_pay_charges' => 'boolean',
            'allow_custom_amount' => 'boolean',
        ]);

        // Calculate target amount
        $targetAmount = ($validated['contribution_amount'] ?? 0) * ($validated['participant_goal'] ?? 0);

        $collection = Collection::create([
            'owner_id' => Auth::id(),
            'name' => $page1Data['name'],
            'description' => $page1Data['description'] ?? null,
            'category' => $page1Data['type'] ?? 'group',
            'icon' => $this->getIconForType($page1Data['type'] ?? 'group'),
            'contribution_amount' => $validated['contribution_amount'] ?? 0,
            'participant_goal' => $validated['participant_goal'] ?? 0,
            'number_of_people' => $validated['participant_goal'] ?? 0,
            'target_amount' => $targetAmount,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'allow_half_payment' => $validated['allow_half_payment'] ?? false,
            'anonymous_payments' => $validated['anonymous_payments'] ?? false,
            'organizer_pay_charges' => $validated['organizer_pay_charges'] ?? false,
            'allow_custom_amount' => $validated['allow_custom_amount'] ?? false,
            'status' => 'active',
            'type' => 'fixed',
        ]);

        $request->session()->forget('collection_data');

        return redirect()->route('collections.live', ['collection' => $collection->id]);
    }

    /**
     * Display the live collection preview page.
     */
    public function live(Collection $collection): Response
    {
        $halfPaymentAmount = $collection->contribution_amount > 0 ? ceil($collection->contribution_amount / 2) : 0;
        $feePercentage = 2.0; // Gathr 0.5% + Gateway 1.5%
        $feeAmount = $collection->contribution_amount > 0 ? ceil($collection->contribution_amount * (1 + ($feePercentage / 100))) : 0;

        // Generate URL slug from collection name
        $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $collection->name));

        // Get owner's bank account info
        $owner = $collection->owner;
        $bankInfo = $owner && $owner->bank_name && $owner->bank_account_number && $owner->bank_account_name
            ? "{$owner->bank_name} · " . substr($owner->bank_account_number, -4)
            : 'N/A';

        $computedTargetAmount = $collection->target_amount;
        if ($computedTargetAmount === null) {
            $computedTargetAmount = ($collection->contribution_amount ?? 0) * ($collection->participant_goal ?? 0);
        }

        return Inertia::render('Collection/Live', [
            'user' => [
                'name' => Auth::user()->name,
            ],
            'reputation' => $this->getUserReputation(),
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name,
                'slug' => $slug,
                'description' => $collection->description,
                'category' => $collection->category,
                'category_label' => $this->getCategoryLabel($collection->category ?? 'group'),
                'icon' => $collection->icon ?? 'group',
                'contribution_amount' => $collection->contribution_amount,
                'participant_goal' => $collection->participant_goal,
                'number_of_people' => $collection->number_of_people ?? $collection->participant_goal,
                'target_amount' => $computedTargetAmount,
                'starts_at' => $collection->starts_at?->format('d M Y'),
                'ends_at' => $collection->ends_at?->format('d M Y'),
                'allow_half_payment' => $collection->allow_half_payment,
                'half_payment_amount' => $halfPaymentAmount,
                'anonymous_payments' => $collection->anonymous_payments,
                'organizer_pay_charges' => $collection->organizer_pay_charges,
                'allow_custom_amount' => $collection->allow_custom_amount,
                'status' => $collection->status,
                'type' => $collection->type,
                'bank_info' => $bankInfo,
                'owner_name' => $owner?->name ?? 'N/A',
            ],
            'appUrl' => config('app.url'),
            'feePercentage' => $feePercentage,
            'feeAmount' => $feeAmount,
        ]);
    }

    /**
     * Display the collection detail page.
     */
    public function show(Collection $collection): Response
    {
        // Get all participants
        $allParticipants = $collection->participants;

        // Calculate counts
        $paidCount = $allParticipants->where('is_paid', true)->count();
        $halfPaidCount = $allParticipants->filter(function ($p) {
            return $p->amount_paid > 0 && $p->amount_paid < $p->amount_due;
        })->count();
        $unpaidCount = $allParticipants->filter(function ($p) {
            return $p->amount_paid === 0 || $p->amount_paid === null;
        })->count();

        // Include guest payments (user_id is null) - categorize them properly
        $guestPayments = $collection->payments->whereNull('user_id');
        $guestPaidCount = $guestPayments->filter(function ($payment) use ($collection) {
            return $payment->amount >= $collection->contribution_amount;
        })->count();
        $guestHalfPaidCount = $guestPayments->filter(function ($payment) use ($collection) {
            return $payment->amount > 0 && $payment->amount < $collection->contribution_amount;
        })->count();
        
        $totalPaidCount = $paidCount + $guestPaidCount;
        $totalHalfPaidCount = $halfPaidCount + $guestHalfPaidCount;

        $raisedAmount = $collection->total_raised;

        $daysLeft = 0;
        $isExpired = false;
        if ($collection->ends_at) {
            $daysLeft = max(0, now()->diffInDays($collection->ends_at, false));
            $isExpired = $collection->ends_at->isPast();
        }

        $progressPercentage = $collection->target_amount && $collection->target_amount > 0
            ? min(100, ($raisedAmount / $collection->target_amount) * 100)
            : 0;

        // Get payments with user info
        $payments = $collection->payments()
            ->with('user:id,name')
            ->orderByDesc('paid_at')
            ->get()
            ->map(function ($payment) use ($collection) {
                // For guest payments (user_id is null), use customer_name if available
                $isGuestPayment = $payment->user_id === null;
                $payerName = $isGuestPayment 
                    ? ($payment->customer_name ?? 'Anonymous')
                    : ($payment->user?->name ?? 'Anonymous');
                $payerInitials = strtoupper(substr($payerName, 0, 2));
                
                return [
                    'id' => $payment->id,
                    'payer_name' => $payerName,
                    'payer_initials' => $payerInitials,
                    'amount' => $payment->amount,
                    'note' => $payment->note,
                    'paid_at' => $payment->paid_at?->diffForHumans() ?? 'Just now',
                    'is_half_payment' => $payment->amount < $collection->contribution_amount,
                ];
            });

        // Get participants for payment summary (with eager loaded user)
        $participants = $collection->participants()
            ->with('user:id,name')
            ->get()
            ->map(function ($participant) use ($collection) {
                $isHalfPaid = $participant->amount_paid > 0 && $participant->amount_paid < $participant->amount_due;
                return [
                    'id' => $participant->id,
                    'user_name' => $participant->user?->name ?? 'Unknown',
                    'user_initials' => strtoupper(substr($participant->user?->name ?? 'U', 0, 2)),
                    'amount_paid' => $participant->amount_paid,
                    'amount_due' => $participant->amount_due,
                    'is_paid' => $participant->is_paid,
                    'is_half_paid' => $isHalfPaid,
                    'status' => $participant->is_paid ? 'paid' : ($isHalfPaid ? 'half_paid' : 'unpaid'),
                ];
            });

        return Inertia::render('Collection/Show', [
            'user' => [
                'name' => Auth::user()->name,
            ],
            'collection' => [
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
                'allow_half_payment' => $collection->allow_half_payment,
            ],
            'stats' => [
                'paid_count' => $totalPaidCount,
                'half_paid_count' => $totalHalfPaidCount,
                'unpaid_count' => $unpaidCount,
                'total_participants' => $allParticipants->count(),
                'raised_amount' => $raisedAmount,
                'days_left' => $daysLeft,
                'is_expired' => $isExpired,
                'progress_percentage' => round($progressPercentage, 1),
            ],
            'reputation' => $this->getUserReputation(),
            'payments' => $payments,
            'participants' => $participants,
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
    private function getCategoryLabel(string $category): string
    {
        return match($category) {
            'group' => 'Group Collection',
            'event' => 'Event Tickets',
            'business' => 'Campus Business',
            default => 'Group Collection',
        };
    }

    /**
     * Get icon for collection type.
     */
    private function getIconForType(string $type): string
    {
        return match($type) {
            'group' => 'group',
            'event' => 'celebration',
            'business' => 'shopping_cart',
            default => 'group',
        };
    }

    /**
     * Display the collection edit page 1.
     */
    public function editPage1(Collection $collection): Response
    {
        // Verify ownership
        if ($collection->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('Collection/EditPage1', [
            'user' => [
                'name' => Auth::user()->name,
            ],
            'reputation' => $this->getUserReputation(),
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name,
                'description' => $collection->description,
                'type' => $collection->category,
            ],
        ]);
    }

    /**
     * Update collection data from edit page 1 in session.
     */
    public function updatePage1(Request $request, Collection $collection): RedirectResponse
    {
        // Verify ownership
        if ($collection->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:group,event,business',
        ]);

        $request->session()->put('edit_collection_data', array_merge(
            ['collection_id' => $collection->id],
            $validated
        ));

        return redirect()->route('collections.edit.page2', ['collection' => $collection->id]);
    }

    /**
     * Display the collection edit page 2.
     */
    public function editPage2(Collection $collection, Request $request): Response
    {
        // Verify ownership
        if ($collection->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Get existing collection data
        $collectionData = [
            'name' => $collection->name,
            'description' => $collection->description,
            'type' => $collection->category,
            'contribution_amount' => $collection->contribution_amount,
            'participant_goal' => $collection->participant_goal,
            'starts_at' => $collection->starts_at?->format('Y-m-d'),
            'ends_at' => $collection->ends_at?->format('Y-m-d'),
            'allow_half_payment' => $collection->allow_half_payment,
            'anonymous_payments' => $collection->anonymous_payments,
            'organizer_pay_charges' => $collection->organizer_pay_charges,
            'allow_custom_amount' => $collection->allow_custom_amount,
        ];

        return Inertia::render('Collection/EditPage2', [
            'user' => [
                'name' => Auth::user()->name,
            ],
            'reputation' => $this->getUserReputation(),
            'collection' => [
                'id' => $collection->id,
            ],
            'collectionData' => $collectionData,
        ]);
    }

    /**
     * Update collection.
     */
    public function update(Request $request, Collection $collection): RedirectResponse
    {
        // Verify ownership
        if ($collection->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $request->merge([
            'allow_half_payment' => $request->boolean('allow_half_payment'),
            'anonymous_payments' => $request->boolean('anonymous_payments'),
            'organizer_pay_charges' => $request->boolean('organizer_pay_charges'),
            'allow_custom_amount' => $request->boolean('allow_custom_amount'),
        ]);

        $validated = $request->validate([
            'contribution_amount' => 'nullable|integer|min:0',
            'participant_goal' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'allow_half_payment' => 'boolean',
            'anonymous_payments' => 'boolean',
            'organizer_pay_charges' => 'boolean',
            'allow_custom_amount' => 'boolean',
        ]);

        // Calculate target amount
        $targetAmount = ($validated['contribution_amount'] ?? 0) * ($validated['participant_goal'] ?? 0);

        $collection->update([
            'contribution_amount' => $validated['contribution_amount'] ?? 0,
            'participant_goal' => $validated['participant_goal'] ?? 0,
            'number_of_people' => $validated['participant_goal'] ?? 0,
            'target_amount' => $targetAmount,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'allow_half_payment' => $validated['allow_half_payment'] ?? false,
            'anonymous_payments' => $validated['anonymous_payments'] ?? false,
            'organizer_pay_charges' => $validated['organizer_pay_charges'] ?? false,
            'allow_custom_amount' => $validated['allow_custom_amount'] ?? false,
        ]);

        return redirect()->route('collections.live', ['collection' => $collection->id])
            ->with('success', 'Collection updated successfully.');
    }
}
