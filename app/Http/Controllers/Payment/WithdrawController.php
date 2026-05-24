<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Services\FlutterwaveService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WithdrawController extends Controller
{
    public function __construct(private readonly FlutterwaveService $flutterwave)
    {
    }

    /**
     * Display the withdrawal page for a collection.
     */
    public function show(Collection $collection): Response
    {
        $raisedAmount    = $collection->total_raised;
        $availableBalance = $collection->available_balance;
        $pendingWithdrawals = $collection->withdrawals()
            ->whereIn('status', ['pending', 'processing', 'pending_authorization'])
            ->orderByDesc('created_at')
            ->get();
        $pendingWithdrawalTotal = (float) $collection->withdrawals()
            ->whereIn('status', ['pending', 'processing', 'pending_authorization'])
            ->sum(DB::raw('amount + fees'));

        $withdrawableNow = max(0, $availableBalance - $pendingWithdrawalTotal);

        $isOrganizerPaying = $collection->organizer_pay_charges;
        $gatewayFee        = $isOrganizerPaying ? round($availableBalance * 0.015, 2) : 0;
        $gathrFee          = $isOrganizerPaying ? round($availableBalance * 0.005, 2) : 0;
        $totalFees         = round($gatewayFee + $gathrFee, 2);
        $youReceive        = round($availableBalance - $totalFees, 2);

        $totalParticipants    = $collection->participant_goal;
        $paidParticipantsCount = $collection->participants->where('is_paid', true)->count();
        $guestPaymentCount    = $collection->payments->whereNull('user_id')->count();
        $totalPaidCount       = $paidParticipantsCount + $guestPaymentCount;
        $unpaidParticipants   = max(0, $totalParticipants - $totalPaidCount);
        $hasUnpaidParticipants = $unpaidParticipants > 0;

        $owner = $collection->owner;
        $bankInfo = [
            'bank_name'          => $owner->bank_name ?? 'Not set',
            'account_number'     => $owner->bank_account_number ? substr($owner->bank_account_number, 0, 4) . '****' . substr($owner->bank_account_number, -4) : 'Not set',
            'account_name'       => $owner->bank_account_name ?? $owner->name,
            'has_bank_details'   => $owner->bank_name && $owner->bank_account_number && $owner->bank_account_name,
            'full_account_number'=> $owner->bank_account_number ?? '',
        ];

        $daysSinceEnd = 0;
        $isExpired    = false;
        if ($collection->ends_at) {
            $daysSinceEnd = now()->diffInDays($collection->ends_at, false);
            $isExpired    = $collection->ends_at->isPast();
        }

        return Inertia::render('Payment/Withdraw', [
            'user' => [
                'name' => Auth::user()->name,
            ],
            'reputation' => $this->getUserReputation(),
            'collection' => [
                'id'                   => $collection->id,
                'name'                 => $collection->name,
                'icon'                 => $collection->icon,
                'category'             => $collection->category,
                'status'               => $collection->status,
                'ends_at'              => $collection->ends_at?->format('d M Y'),
                'organizer_pay_charges'=> $collection->organizer_pay_charges,
            ],
            'balance' => [
                'total_balance'            => round($availableBalance, 0),
                'withdrawable_now'         => round($withdrawableNow, 0),
                'total_collected'          => round($raisedAmount, 0),
                'pending_withdrawal_total' => round($pendingWithdrawalTotal, 0),
                'gateway_fee'              => round($gatewayFee, 0),
                'gateway_fee_percentage'   => 1.5,
                'gathr_fee'                => round($gathrFee, 0),
                'gathr_fee_percentage'     => 0.5,
                'total_fees'               => round($totalFees, 0),
                'you_receive'              => round($youReceive, 0),
            ],
            'withdrawal_state' => [
                'has_pending'   => $pendingWithdrawals->isNotEmpty(),
                'pending_count' => $pendingWithdrawals->count(),
                'pending_total' => round($pendingWithdrawalTotal, 0),
            ],
            'participants' => [
                'total'     => $totalParticipants,
                'paid'      => $totalPaidCount,
                'unpaid'    => $unpaidParticipants,
                'has_unpaid'=> $hasUnpaidParticipants,
            ],
            'bank_info'     => $bankInfo,
            'is_expired'    => $isExpired,
            'days_since_end'=> $daysSinceEnd,
            'auth' => [
                'user' => [
                    'bank_name'           => $owner->bank_name,
                    'bank_account_number' => $owner->bank_account_number,
                    'bank_account_name'   => $owner->bank_account_name,
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

        if ($collection->owner_id !== Auth::id()) {
            return back()->with('error', 'You are not allowed to withdraw from this collection.');
        }

        $owner = $collection->owner;
        if (! $owner->bank_name || ! $owner->bank_account_number || ! $owner->bank_account_name) {
            return back()->with('error', 'Please add your bank account details before withdrawing.');
        }

        $bankCode = $this->flutterwave->resolveBankCode($owner->bank_name);
        if (! $bankCode) {
            return back()->with('error', 'Unable to resolve the bank code for your payout bank.');
        }

        $withdrawAmount = (int) round((float) $request->amount, 0);
        $withdrawal     = null;
        $grossReference = null;

        try {
            DB::transaction(function () use ($collection, $withdrawAmount, $owner, &$withdrawal, &$grossReference) {
                $lockedCollection = Collection::query()
                    ->whereKey($collection->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedCollection->withdrawals()
                    ->whereIn('status', ['pending', 'processing', 'pending_authorization'])
                    ->exists()) {
                    throw new \RuntimeException('A withdrawal request is already pending for this collection.');
                }

                $availableBalance = $lockedCollection->available_balance;

                if ($availableBalance <= 0 || $withdrawAmount > $availableBalance) {
                    throw new \RuntimeException('Insufficient funds available for withdrawal.');
                }

                $owner = $lockedCollection->owner;
                if (! $owner->bank_name || ! $owner->bank_account_number || ! $owner->bank_account_name) {
                    throw new \RuntimeException('Please add your bank account details before withdrawing.');
                }

                $grossReference = 'GATHR_WD_' . $lockedCollection->id . '_' . now()->format('YmdHis') . '_' . strtoupper(substr(md5(uniqid((string) $lockedCollection->id, true)), 0, 8));

                $isOrganizerPaying = $lockedCollection->organizer_pay_charges;
                $gatewayFee = $isOrganizerPaying ? (int) round($withdrawAmount * 0.015, 0) : 0;
                $gathrFee   = $isOrganizerPaying ? (int) round($withdrawAmount * 0.005, 0) : 0;
                $totalFees  = $gatewayFee + $gathrFee;
                $youReceive = $withdrawAmount - $totalFees;

                $withdrawal = $lockedCollection->withdrawals()->create([
                    'user_id'               => $owner->id,
                    'amount'                => $youReceive,
                    'fees'                  => $totalFees,
                    'status'                => 'pending',
                    'transaction_reference' => $grossReference,
                    'failure_reason'        => null,
                    'processed_at'          => null,
                ]);
            });

            $destinationAccountName = $this->flutterwave->validateDestinationAccount(
                $bankCode,
                $owner->bank_account_number
            );

            $transferResponse = $this->flutterwave->initiateSingleTransfer([
                'account_bank'    => $bankCode,
                'account_number'  => $owner->bank_account_number,
                'amount'          => $withdrawAmount,
                'narration'       => sprintf('Gathr payout for collection #%d - %s', $collection->id, $collection->name),
                'currency'        => 'NGN',
                'reference'       => $grossReference,
                'debit_currency'  => 'NGN',
                'beneficiary_name'=> $destinationAccountName,
            ]);

            $transferStatus = strtoupper((string) ($transferResponse['status'] ?? ''));
            $flwReference   = $transferResponse['reference'] ?? $grossReference;

            if ($transferStatus === 'SUCCESSFUL') {
                $withdrawal?->update([
                    'status'                => 'completed',
                    'transaction_reference' => $flwReference,
                    'failure_reason'        => null,
                    'processed_at'          => now(),
                ]);

                return redirect()->route('collections.withdraw', $collection)
                    ->with('success', 'Withdrawal successful! Funds are on their way to your bank account.');
            }

            if (in_array($transferStatus, ['NEW', 'PENDING'], true)) {
                $withdrawal?->update([
                    'status'                => 'processing',
                    'transaction_reference' => $flwReference,
                    'failure_reason'        => null,
                ]);

                return redirect()->route('collections.withdraw', $collection)
                    ->with('success', 'Withdrawal is being processed and will complete shortly.');
            }

            $failureReason = $transferResponse['complete_message'] ?? 'Flutterwave disbursement failed.';

            $withdrawal?->update([
                'status'                => 'failed',
                'transaction_reference' => $flwReference,
                'failure_reason'        => $failureReason,
            ]);

            return back()->with('error', $failureReason);

        } catch (\RuntimeException $e) {
            if ($withdrawal) {
                $withdrawal->update([
                    'status'         => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
            }

            return back()->with('error', $e->getMessage());

        } catch (\Throwable $e) {
            if ($withdrawal) {
                $withdrawal->update([
                    'status'         => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
            }

            return back()->with('error', 'Withdrawal payout failed: ' . $e->getMessage());
        }
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
        $allParticipants   = $collection->participants;
        $paidParticipants  = $allParticipants->where('is_paid', true);
        $unpaidParticipants = $allParticipants->filter(function ($p) {
            return ! $p->is_paid || $p->amount_paid === 0 || $p->amount_paid === null;
        });

        $guestPaymentCount = $collection->payments->whereNull('user_id')->count();
        $paidCount         = $paidParticipants->count() + $guestPaymentCount;
        $totalCount        = $collection->participant_goal;
        $unpaidCount       = max(0, $totalCount - $paidCount);

        $daysLeft        = 0;
        $endsAtFormatted = '';
        if ($collection->ends_at) {
            $daysLeft        = round(max(0, now()->diffInDays($collection->ends_at, false)));
            $endsAtFormatted = $collection->ends_at->format('l d M');
        }

        $paymentLink = config('app.url') . '/c/' . strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $collection->name)) . '-' . $collection->id;

        $unpaidList = $unpaidParticipants->map(function ($participant) {
            return [
                'id'          => $participant->id,
                'name'        => $participant->user?->name ?? 'Unknown',
                'amount_due'  => $participant->amount_due,
                'amount_paid' => $participant->amount_paid,
            ];
        });

        return Inertia::render('Collection/Remainder', [
            'user' => [
                'name' => Auth::user()->name,
            ],
            'reputation' => $this->getUserReputation(),
            'collection' => [
                'id'                  => $collection->id,
                'name'                => $collection->name,
                'icon'                => $collection->icon,
                'contribution_amount' => $collection->contribution_amount,
                'participant_goal'    => $totalCount,
                'ends_at'             => $endsAtFormatted,
            ],
            'stats' => [
                'paid_count'   => $paidCount,
                'unpaid_count' => $unpaidCount,
                'total'        => $totalCount,
                'days_left'    => $daysLeft,
            ],
            'payment_link'        => $paymentLink,
            'unpaid_participants' => $unpaidList,
        ]);
    }

    /**
     * Send reminder to unpaid participants.
     */
    public function sendReminder(Request $request, Collection $collection): RedirectResponse
    {
        $request->validate([
            'message'    => 'nullable|string|max:1000',
            'channels'   => 'required|array',
            'channels.*' => 'in:whatsapp,sms,email',
        ]);

        $unpaidParticipants = $collection->participants->filter(function ($p) {
            return ! $p->is_paid || $p->amount_paid === 0 || $p->amount_paid === null;
        });

        return back()->with('success', 'Reminder sent to ' . $unpaidParticipants->count() . ' unpaid participants.');
    }

    /**
     * Update user bank account details.
     */
    public function updateBank(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name'           => 'required|string|max:255',
            'bank_account_number' => 'required|string|min:10|max:10',
            'bank_account_name'   => 'required|string|max:255',
        ]);

        $request->user()->update($validated);

        return back()->with('success', 'Bank account details updated successfully.');
    }

    private function getUserReputation(): array
    {
        $user               = Auth::user();
        $participationCount = $user->participations()->count() + $user->collections()->count();

        $tiers = [
            ['name' => 'Starter',      'level' => 1, 'min' => 0, 'next_at' => 2],
            ['name' => 'Rising Rep',   'level' => 2, 'min' => 2, 'next_at' => 4],
            ['name' => 'Campus Mogul', 'level' => 3, 'min' => 4, 'next_at' => null],
        ];

        $current = $tiers[0];
        foreach ($tiers as $tier) {
            if ($participationCount >= $tier['min']) {
                $current = $tier;
            }
        }

        return [
            'name'  => $current['name'],
            'level' => $current['level'],
        ];
    }
}
