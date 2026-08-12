<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Mail\WithdrawalNotification;
use App\Models\Collection;
use App\Models\GuestPayment;
use App\Models\Withdrawal;
use App\Services\ZainPayService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class WithdrawController extends Controller
{
    public function __construct(private readonly ZainPayService $zainpay)
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

        if ($isOrganizerPaying) {
            // Organizer absorbs: Gathr 1.5% + ZainPay ₦25 flat transfer fee (one combined deduction)
            $gathrFee    = (int) round($availableBalance * 0.015, 0);
            $gatewayFee  = 25; // ZainPay flat bank transfer fee
            $totalFees   = $gathrFee + $gatewayFee;
            $youReceive  = max(0, round($availableBalance - $totalFees, 2));
        } else {
            // Payers already paid upfront (Option A). Organizer receives full balance.
            // ZainPay's ₦25 transfer fee is covered by the accumulated fee pool in the ISA.
            $gathrFee   = 0;
            $gatewayFee = 0;
            $totalFees  = 0;
            $youReceive = round($availableBalance, 2);
        }

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
                'gateway_fee'              => round($gatewayFee, 0), // ₦25 flat (organizer mode only)
                'gathr_fee'                => round($gathrFee, 0),   // 1.5% Gathr margin (organizer mode only)
                'total_fees'               => round($totalFees, 0),
                'you_receive'              => round($youReceive, 0),
                'organizer_pays'           => $isOrganizerPaying,
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
        // Allow up to 180s — ZainPay transfer endpoint can be slow under load (60s timeout + buffer).
        set_time_limit(180);

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

        // Prefer the verified bank_code saved at account-setup time.
        // Fall back to fuzzy resolution only for accounts saved before this fix.
        $bankCode = $owner->bank_code ?: $this->zainpay->resolveBankCode($owner->bank_name);
        if (! $bankCode) {
            return back()->with('error', 'Unable to resolve the bank code for your payout bank. Please re-save your bank details.');
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

                if ($isOrganizerPaying) {
                    $gathrFee   = (int) round($withdrawAmount * 0.015, 0);
                    $gatewayFee = 25; // ZainPay flat transfer fee
                    $totalFees  = $gathrFee + $gatewayFee;
                    $youReceive = $withdrawAmount - $totalFees;
                } else {
                    // Payers already paid all fees upfront — organizer gets full balance.
                    $gathrFee   = 0;
                    $gatewayFee = 0;
                    $totalFees  = 0;
                    $youReceive = $withdrawAmount;
                }

                if ($youReceive <= 0) {
                    throw new \RuntimeException('Withdrawal amount is too low after fees.');
                }

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

            // ZainPay requires the source virtual account for the transfer
            $amountKobo    = ($withdrawal?->amount ?? $withdrawAmount) * 100;
            $sourceAccount = $this->zainpay->findSourceAccount($amountKobo);

            if (! $sourceAccount || empty($sourceAccount['accountNumber'])) {
                throw new \RuntimeException('No ZainPay virtual account found for withdrawal. Set ZAINPAY_SOURCE_VA_NUMBER in your environment.');
            }

            $this->zainpay->initiateTransfer(
                $owner->bank_account_number,
                $bankCode,
                $amountKobo,
                $sourceAccount['accountNumber'],
                $sourceAccount['bankCode'],
                $grossReference,
                sprintf('Gathr payout for collection #%d - %s', $collection->id, $collection->name)
            );

            // ZainPay processes transfers asynchronously; webhook confirms completion
            $withdrawal?->update([
                'status'         => 'processing',
                'failure_reason' => null,
            ]);

            $this->notifyContributors($collection, $withdrawAmount);

            return redirect()->route('collections.withdraw', $collection)
                ->with('success', 'Withdrawal submitted. Funds will arrive in your bank account shortly.');

        } catch (ConnectionException $e) {
            // ZainPay did not respond in time. The transfer may already be in flight.
            // Leave the withdrawal as 'processing' so the organizer cannot submit again
            // while we wait for the webhook to confirm or deny the transfer.
            if ($withdrawal) {
                $withdrawal->update([
                    'status'         => 'processing',
                    'failure_reason' => 'ZainPay did not respond in time — awaiting webhook confirmation.',
                ]);
            }
            Log::warning('Withdrawal transfer timeout', [
                'collection_id' => $collection->id,
                'withdrawal_id' => $withdrawal?->id,
                'error'         => $e->getMessage(),
            ]);
            return back()->with('error', 'Your withdrawal was submitted but ZainPay did not respond in time. Funds are likely on their way — status will update automatically once ZainPay confirms.');

        } catch (\RuntimeException $e) {
            // ZainPay returned a definitive rejection code (insufficient funds, invalid account, etc.).
            // Safe to mark failed — ZainPay did not send any money.
            if ($withdrawal) {
                $withdrawal->update([
                    'status'         => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
            }
            return back()->with('error', $e->getMessage());

        } catch (\Throwable $e) {
            // Unknown error after the transfer call was already dispatched.
            // Keep as 'processing' — we cannot confirm whether ZainPay acted on the request.
            if ($withdrawal) {
                $withdrawal->update([
                    'status'         => 'processing',
                    'failure_reason' => 'Unexpected error after dispatch: ' . substr($e->getMessage(), 0, 200) . ' — awaiting webhook.',
                ]);
            }
            Log::error('Withdrawal unexpected error', [
                'collection_id' => $collection->id,
                'withdrawal_id' => $withdrawal?->id,
                'error'         => $e->getMessage(),
            ]);
            return back()->with('error', 'Withdrawal submitted but an unexpected error occurred. Please wait a few minutes — status will update automatically.');
        }
    }

    /**
     * JSON endpoint: poll ZainPay for a specific withdrawal's status.
     * Called by the frontend every few seconds while status is "processing".
     */
    public function withdrawalStatus(Collection $collection): \Illuminate\Http\JsonResponse
    {
        if ($collection->owner_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $withdrawal = $collection->withdrawals()
            ->whereIn('status', ['processing', 'pending'])
            ->orderByDesc('created_at')
            ->first();

        if (! $withdrawal) {
            return response()->json(['status' => 'none']);
        }

        // Already know the final state
        if (in_array($withdrawal->status, ['completed', 'failed'])) {
            return response()->json(['status' => $withdrawal->status]);
        }

        // Ask ZainPay using the outbound transfer verify endpoint — NOT the deposit endpoint.
        // zainbox/transactions only lists inbound payments; withdrawals are outbound transfers.
        try {
            $txnRef = $withdrawal->transaction_reference;
            $result = $this->zainpay->verifyTransfer($txnRef);

            if ($result) {
                $txStatus = strtolower($result['status'] ?? $result['txnStatus'] ?? $result['transactionType'] ?? '');

                $successStatuses = ['successful', 'success', 'completed', 'transferred', 'transfer', 'debit'];
                $failStatuses    = ['failed', 'cancelled', 'reversed', 'rejected', 'error'];

                if (in_array($txStatus, $successStatuses)) {
                    $withdrawal->update(['status' => 'completed', 'processed_at' => now(), 'failure_reason' => null]);
                    return response()->json(['status' => 'completed']);
                }

                if (in_array($txStatus, $failStatuses)) {
                    $withdrawal->update([
                        'status'         => 'failed',
                        'failure_reason' => $result['description'] ?? $result['failureReason'] ?? 'Transfer failed per ZainPay.',
                        'processed_at'   => now(),
                    ]);
                    return response()->json(['status' => 'failed']);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Withdrawal status poll error: ' . $e->getMessage(), ['withdrawal_id' => $withdrawal->id]);
        }

        return response()->json(['status' => $withdrawal->status]);
    }

    /**
     * Poll ZainPay and confirm a processing withdrawal.
     * Useful when webhooks are unavailable (local dev or delayed).
     */
    public function confirmWithdrawal(Collection $collection): RedirectResponse
    {
        if ($collection->owner_id !== Auth::id()) {
            return back()->with('error', 'Unauthorized.');
        }

        $withdrawal = $collection->withdrawals()
            ->whereIn('status', ['processing', 'pending'])
            ->orderByDesc('created_at')
            ->first();

        if (! $withdrawal) {
            return back()->with('error', 'No processing withdrawal found.');
        }

        try {
            $txnRef = $withdrawal->transaction_reference;

            // Use the outbound transfer verify endpoint — zainbox/transactions only shows inbound deposits.
            $result = $this->zainpay->verifyTransfer($txnRef);

            Log::info('Withdrawal confirm poll', ['txnRef' => $txnRef, 'result' => $result]);

            if ($result) {
                $txStatus = strtolower($result['status'] ?? $result['txnStatus'] ?? $result['transactionType'] ?? '');

                $successStatuses = ['successful', 'success', 'completed', 'transferred', 'transfer', 'debit'];
                $failStatuses    = ['failed', 'cancelled', 'reversed', 'rejected', 'error'];

                if (in_array($txStatus, $successStatuses)) {
                    $withdrawal->update(['status' => 'completed', 'processed_at' => now(), 'failure_reason' => null]);
                    return back()->with('success', 'Withdrawal confirmed as completed.');
                }

                if (in_array($txStatus, $failStatuses)) {
                    $reason = $result['description'] ?? $result['failureReason'] ?? 'Transfer was rejected by ZainPay.';
                    $withdrawal->update(['status' => 'failed', 'failure_reason' => $reason, 'processed_at' => now()]);
                    return back()->with('error', 'Withdrawal failed: ' . $reason);
                }
            }

        } catch (\Throwable $e) {
            Log::error('Withdrawal confirm error: ' . $e->getMessage());
        }

        return back()->with('error', 'Transfer is still processing. Please wait a moment — ZainPay will confirm automatically via webhook.');
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
            'bank_code'           => 'nullable|string|max:20',
            'bank_account_number' => 'required|string|min:10|max:10',
            'bank_account_name'   => 'required|string|max:255',
        ]);

        $request->user()->update($validated);

        return back()->with('success', 'Bank account details updated successfully.');
    }

    private function notifyContributors(Collection $collection, float $withdrawalAmount): void
    {
        $organizerName = $collection->owner->name ?? 'The organizer';
        $totalRaised   = $collection->total_raised;
        $slug          = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $collection->name)) . '-' . $collection->id;

        // Collect emails from guest payments
        $guestEmails = GuestPayment::where('collection_id', $collection->id)
            ->where('status', 'completed')
            ->whereNotNull('customer_email')
            ->where('customer_email', '!=', '')
            ->where('is_anonymous', false)
            ->get(['customer_name', 'customer_email']);

        // Collect emails from registered participants
        $participantEmails = $collection->participants()
            ->with('user:id,name,email')
            ->where('is_paid', true)
            ->get()
            ->filter(fn($p) => $p->user && $p->user->email)
            ->map(fn($p) => (object)[
                'customer_name'  => $p->user->name,
                'customer_email' => $p->user->email,
            ]);

        $allContributors = $guestEmails->concat($participantEmails)
            ->unique('customer_email');

        foreach ($allContributors as $contributor) {
            try {
                Mail::to($contributor->customer_email)->send(new WithdrawalNotification(
                    contributorName:  $contributor->customer_name ?? 'Contributor',
                    collectionName:   $collection->name,
                    organizerName:    $organizerName,
                    withdrawalAmount: $withdrawalAmount,
                    totalRaised:      $totalRaised,
                    collectionSlug:   $slug,
                ));
            } catch (\Throwable $e) {
                Log::warning('Failed to send withdrawal notification email', [
                    'email' => $contributor->customer_email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
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
