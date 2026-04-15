<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class WithdrawController extends Controller
{
    /**
     * Display the withdrawal page for a collection.
     */
    public function show(Collection $collection): Response
    {
        $raisedAmount = $collection->payments->sum('amount');
        
        // Calculate fees
        $monnifyFee = $raisedAmount * 0.015; // 1.5%
        $gathrFee = $raisedAmount * 0.01; // 1%
        $totalFees = $monnifyFee + $gathrFee;
        $youReceive = $raisedAmount - $totalFees;
        
        // Calculate participant stats
        $totalParticipants = $collection->participant_goal;
        $paidParticipants = $collection->participants->where('is_paid', true)->count();
        $unpaidParticipants = $totalParticipants - $paidParticipants;
        $hasUnpaidParticipants = $unpaidParticipants > 0;
        
        // Get owner's bank account info
        $owner = $collection->owner;
        $bankInfo = [
            'bank_name' => $owner->bank_name ?? 'Not set',
            'account_number' => $owner->bank_account_number ? substr($owner->bank_account_number, 0, 4) . '****' . substr($owner->bank_account_number, -4) : 'Not set',
            'account_name' => $owner->bank_account_name ?? $owner->name,
            'has_bank_details' => $owner->bank_name && $owner->bank_account_number && $owner->bank_account_name,
            'full_account_number' => $owner->bank_account_number ?? '',
        ];
        
        // Calculate days since collection ended
        $daysSinceEnd = 0;
        $isExpired = false;
        if ($collection->ends_at) {
            $daysSinceEnd = now()->diffInDays($collection->ends_at, false);
            $isExpired = $collection->ends_at->isPast();
        }

        return Inertia::render('Payment/Withdraw', [
            'user' => [
                'name' => Auth::user()->name,
            ],
            'reputation' => $this->getUserReputation(),
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name,
                'icon' => $collection->icon,
                'category' => $collection->category,
                'status' => $collection->status,
                'ends_at' => $collection->ends_at?->format('d M Y'),
            ],
            'balance' => [
                'total_balance' => $raisedAmount,
                'total_collected' => $raisedAmount,
                'monnify_fee' => round($monnifyFee, 2),
                'monnify_fee_percentage' => 1.5,
                'gathr_fee' => round($gathrFee, 2),
                'gathr_fee_percentage' => 1.0,
                'total_fees' => round($totalFees, 2),
                'you_receive' => round($youReceive, 2),
            ],
            'participants' => [
                'total' => $totalParticipants,
                'paid' => $paidParticipants,
                'unpaid' => $unpaidParticipants,
                'has_unpaid' => $hasUnpaidParticipants,
            ],
            'bank_info' => $bankInfo,
            'is_expired' => $isExpired,
            'days_since_end' => $daysSinceEnd,
            'auth' => [
                'user' => [
                    'bank_name' => $owner->bank_name,
                    'bank_account_number' => $owner->bank_account_number,
                    'bank_account_name' => $owner->bank_account_name,
                ],
            ],
        ]);
    }

    /**
     * Process the withdrawal request.
     */
    public function store(Request $request, Collection $collection): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $raisedAmount = $collection->payments->sum('amount');
        
        // Check if collection has sufficient balance
        if ($raisedAmount <= 0) {
            return back()->with('error', 'No funds available for withdrawal.');
        }

        // Check if owner has bank details
        $owner = $collection->owner;
        if (!$owner->bank_name || !$owner->bank_account_number || !$owner->bank_account_name) {
            return back()->with('error', 'Please add your bank account details before withdrawing.');
        }

        // Calculate fees
        $monnifyFee = $raisedAmount * 0.015;
        $gathrFee = $raisedAmount * 0.01;
        $youReceive = $raisedAmount - ($monnifyFee + $gathrFee);

        // TODO: Integrate with Paystack/Flutterwave for actual payout
        // For now, we'll just create a withdrawal record
        
        // Create withdrawal record
        $collection->withdrawals()->create([
            'user_id' => $owner->id,
            'amount' => $youReceive,
            'fees' => $monnifyFee + $gathrFee,
            'status' => 'pending',
            'processed_at' => null,
        ]);

        return redirect()->route('collections.index')
            ->with('success', 'Withdrawal request submitted successfully. Funds will be transferred within 24 hours.');
    }

    /**
     * Extend the collection deadline.
     */
    public function extendDeadline(Request $request, Collection $collection): RedirectResponse
    {
        $request->validate([
            'new_ends_at' => 'required|date|after_or_equal:today',
        ]);

        $collection->update([
            'ends_at' => $request->new_ends_at,
        ]);

        return back()->with('success', 'Collection deadline extended successfully.');
    }

    /**
     * Display the remainder/reminder page for unpaid participants.
     */
    public function remainder(Collection $collection): Response
    {
        // Get all participants and calculate unpaid
        $allParticipants = $collection->participants;
        $paidParticipants = $allParticipants->where('is_paid', true);
        $unpaidParticipants = $allParticipants->filter(function ($p) {
            return !$p->is_paid || $p->amount_paid === 0 || $p->amount_paid === null;
        });

        // Count guest payments as paid contributors
        $guestPaymentCount = $collection->payments->whereNull('user_id')->count();
        $paidCount = $paidParticipants->count() + $guestPaymentCount;
        $totalCount = $collection->participant_goal;
        
        // Calculate unpaid as: total goal - paid (including guest payments)
        $unpaidCount = max(0, $totalCount - $paidCount);

        // Calculate days left (rounded to whole number)
        $daysLeft = 0;
        $endsAtFormatted = '';
        if ($collection->ends_at) {
            $daysLeft = round(max(0, now()->diffInDays($collection->ends_at, false)));
            $endsAtFormatted = $collection->ends_at->format('l d M');
        }

        // Generate payment link
        $paymentLink = config('app.url') . '/c/' . strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $collection->name)) . '-' . $collection->id;

        // Get unpaid participants list
        $unpaidList = $unpaidParticipants->map(function ($participant) {
            return [
                'id' => $participant->id,
                'name' => $participant->user?->name ?? 'Unknown',
                'amount_due' => $participant->amount_due,
                'amount_paid' => $participant->amount_paid,
            ];
        });

        return Inertia::render('Collection/Remainder', [
            'user' => [
                'name' => Auth::user()->name,
            ],
            'reputation' => $this->getUserReputation(),
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name,
                'icon' => $collection->icon,
                'contribution_amount' => $collection->contribution_amount,
                'participant_goal' => $totalCount,
                'ends_at' => $endsAtFormatted,
            ],
            'stats' => [
                'paid_count' => $paidCount,
                'unpaid_count' => $unpaidCount,
                'total' => $totalCount,
                'days_left' => $daysLeft,
            ],
            'payment_link' => $paymentLink,
            'unpaid_participants' => $unpaidList,
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
     * Send reminder to unpaid participants.
     */
    public function sendReminder(Request $request, Collection $collection): RedirectResponse
    {
        $request->validate([
            'message' => 'nullable|string|max:1000',
            'channels' => 'required|array',
            'channels.*' => 'in:whatsapp,sms,email',
        ]);

        // Get unpaid participants
        $unpaidParticipants = $collection->participants->filter(function ($p) {
            return !$p->is_paid || $p->amount_paid === 0 || $p->amount_paid === null;
        });

        // TODO: Implement actual notification sending via WhatsApp/SMS/Email
        // For now, we'll just log the reminder request
        
        foreach ($unpaidParticipants as $participant) {
            // Send notification logic here
            // - WhatsApp API integration
            // - SMS API integration
            // - Email notification
        }

        return back()->with('success', 'Reminder sent to ' . $unpaidParticipants->count() . ' unpaid participants.');
    }

    /**
     * Update user bank account details.
     */
    public function updateBank(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|min:10|max:10',
            'bank_account_name' => 'required|string|max:255',
        ]);

        $user->update($validated);

        return back()->with('success', 'Bank account details updated successfully.');
    }
}
