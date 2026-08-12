<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionPayment;
use App\Models\GuestPayment;
use App\Models\Withdrawal;
use App\Services\ZainPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class GuestPaymentController extends Controller
{
    public function __construct(private readonly ZainPayService $zainpay)
    {
    }

    public function show(string $slug): Response|RedirectResponse
    {
        $parts        = explode('-', $slug);
        $collectionId = end($parts);

        $collection = Collection::with('owner')->findOrFail($collectionId);

        if ($collection->isExpired()) {
            return redirect()->route('collections.guest', ['slug' => $slug])
                ->with('error', 'This collection has closed. Payments are no longer accepted.');
        }

        $halfPaymentAmount = $collection->contribution_amount > 0
            ? ceil($collection->contribution_amount / 2)
            : 0;

        $recentPayers = $collection->payments()
            ->orderByDesc('paid_at')
            ->limit(4)
            ->get()
            ->map(function ($payment) {
                $name     = $payment->customer_name ?? $payment->user?->name ?? '?';
                $initials = strtoupper(substr($name, 0, 2));

                return [
                    'name'     => $name,
                    'initials' => $initials ?: '?',
                ];
            });

        $paidParticipants  = $collection->participants()->where('is_paid', true)->count();
        $guestPaymentCount = $collection->guestPayments()->where('status', 'completed')->count();
        $totalPaid         = $paidParticipants + $guestPaymentCount;

        $participantGoal = max(1, (int) $collection->participant_goal);

        return Inertia::render('guest/Pay', [
            'collection' => [
                'id'                    => $collection->id,
                'name'                  => $collection->name,
                'slug'                  => $slug,
                'icon'                  => $collection->icon ?? 'group',
                'contribution_amount'   => $collection->contribution_amount,
                'half_payment_amount'   => $halfPaymentAmount,
                'allow_custom_amount'   => $collection->allow_custom_amount,
                'allow_half_payment'    => $collection->allow_half_payment,
                'anonymous_payments'    => $collection->anonymous_payments,
                'organizer_pay_charges' => $collection->organizer_pay_charges,
                'total_paid'            => $totalPaid,
                'participant_goal'      => $participantGoal,
                'recent_payers'         => $recentPayers,
            ],
            'owner' => [
                'name'     => $collection->owner->name ?? 'Unknown',
                'initials' => strtoupper(substr($collection->owner->name ?? 'U', 0, 2)),
            ],
            // Fee config — single source of truth consumed by Pay.vue
            'fee_config' => [
                'payer_fee_pct'          => 3.0,  // ZainPay 1.5% + Gathr 1.5%
                'transfer_fee_per_payer' => (int) ceil(25 / $participantGoal),
            ],
            'appUrl' => config('app.url'),
        ]);
    }

    public function showMethod(Request $request, string $slug): Response|RedirectResponse
    {
        $parts        = explode('-', $slug);
        $collectionId = end($parts);

        $collection = Collection::findOrFail($collectionId);

        if ($collection->isExpired()) {
            return redirect()->route('collections.guest', ['slug' => $slug])
                ->with('error', 'This collection has closed. Payments are no longer accepted.');
        }

        return Inertia::render('guest/PayMethod', [
            'collection' => [
                'id'   => $collection->id,
                'name' => $collection->name,
                'slug' => $slug,
            ],
            'amount'      => (int) $request->query('amount', 0),
            'base_amount' => (int) $request->query('base_amount', 0),
            'fees'        => (int) $request->query('fees', 0),
            'name'        => $request->query('name', ''),
            'email'       => $request->query('email', ''),
            'isAnonymous' => (bool) $request->query('is_anonymous', false),
            'paymentType' => $request->query('payment_type', 'full'),
        ]);
    }

    /**
     * Initialize payment — returns card redirect URL or NUBAN transfer details.
     */
    public function initiatePayment(Request $request, string $slug)
    {
        $validated = $request->validate([
            'amount'         => 'required|integer|min:1',
            'base_amount'    => 'required|integer|min:1',
            'fees'           => 'required|integer|min:0',
            'name'           => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'is_anonymous'   => 'boolean',
            'payment_type'   => 'required|in:full,half,custom',
            'payment_method' => 'required|in:transfer,card',
            'mode'           => 'nullable|in:inline,redirect,embedded',
        ]);

        $parts        = explode('-', $slug);
        $collectionId = end($parts);
        $collection   = Collection::findOrFail($collectionId);

        if ($collection->isExpired()) {
            return response()->json(['message' => 'This collection has closed and is no longer accepting payments.'], 422);
        }

        $txRef = 'GATHR_' . time() . '_' . strtoupper(substr(md5(uniqid()), 0, 8));

        try {
            $customerName  = $validated['is_anonymous'] ? 'Anonymous' : ($validated['name'] ?? 'Anonymous');
            $customerEmail = $validated['customer_email'] ?? '';

            $paymentAttrs = [
                'collection_id'         => $collectionId,
                'payment_reference'     => $txRef,
                'transaction_reference' => $txRef,
                'customer_name'         => $customerName,
                'customer_email'        => $customerEmail,
                'amount'                => $validated['amount'],
                'fees'                  => $validated['fees'],
                'is_anonymous'          => $validated['is_anonymous'],
                'payment_type'          => $validated['payment_type'],
                'status'                => 'pending',
            ];

            // Embedded mode: ZainPay checkout runs inside an iframe on our page, so
            // its redirect must land on the embedded callback, which breaks out of
            // the frame instead of rendering the receipt inside it.
            $guestCallbackUrl = ($validated['mode'] ?? 'redirect') === 'embedded'
                ? route('collections.guest.pay.callback.embedded', ['slug' => $slug])
                : route('collections.guest.pay.callback', ['slug' => $slug]);

            if ($validated['payment_method'] === 'card') {
                // InlineJS modal (primary): the browser initializes the payment itself
                // with the public inline key — we only persist the pending record and
                // hand back the config the modal needs.
                if (($validated['mode'] ?? 'redirect') === 'inline') {
                    GuestPayment::create($paymentAttrs);

                    return response()->json([
                        'type'       => 'inline',
                        'tx_ref'     => $txRef,
                        'public_key' => config('services.zainpay.inline_key'),
                        'inline'     => [
                            'amount'       => (string) $validated['amount'],
                            'txnRef'       => $txRef,
                            'mobileNumber' => '08000000000', // required by the inline endpoint; guests don't provide one
                            'zainboxCode'  => config('services.zainpay.zainbox_code'),
                            'emailAddress' => $customerEmail ?: 'noreply@gathr.ng',
                            'callBackUrl'  => $guestCallbackUrl,
                            'logoUrl'      => config('app.url') . '/logo.png',
                        ],
                    ]);
                }

                // Hosted checkout fallback: amounts in Naira; ZainPay redirects the
                // payer back to our callback route with ?txnRef= after checkout.
                $redirectUrl = $this->zainpay->initiateCardPayment(
                    (int) $validated['amount'],
                    $txRef,
                    $customerEmail ?: 'noreply@gathr.ng',
                    '',
                    $guestCallbackUrl
                );

                GuestPayment::create($paymentAttrs);

                return response()->json([
                    'type'         => 'card',
                    'tx_ref'       => $txRef,
                    'redirect_url' => $redirectUrl,
                ]);
            }

            // Create the DVA first — only persist the payment record if ZainPay succeeds
            $amountKobo  = (int) $validated['amount'] * 100;
            $callbackUrl = config('app.url') . '/webhooks/zainpay';

            try {
                $dva = $this->zainpay->createDynamicVirtualAccount(
                    $amountKobo,
                    $txRef,
                    $customerEmail ?: 'noreply@gathr.ng',
                    $callbackUrl,
                    2880,
                    config('services.zainpay.dva_bank_type', 'gtBank')
                );
            } catch (\Throwable $dvaError) {
                // ZainPay's DVA API has been down since their partner-bank migration,
                // but their hosted checkout still offers a working bank-transfer tab —
                // send the payer there instead of failing outright.
                Log::warning('DVA create failed, falling back to hosted checkout: ' . $dvaError->getMessage(), ['txnRef' => $txRef]);

                $redirectUrl = $this->zainpay->initiateCardPayment(
                    (int) $validated['amount'],
                    $txRef,
                    $customerEmail ?: 'noreply@gathr.ng',
                    '',
                    $guestCallbackUrl
                );

                GuestPayment::create($paymentAttrs);

                return response()->json([
                    'type'         => 'card',
                    'tx_ref'       => $txRef,
                    'redirect_url' => $redirectUrl,
                ]);
            }

            GuestPayment::create($paymentAttrs);

            return response()->json([
                'type'           => 'transfer',
                'tx_ref'         => $txRef,
                'account_number' => $dva['accountNumber'] ?? '',
                'account_name'   => $dva['accountName'] ?? 'Zainpay Checkout',
                'bank_name'      => $dva['bankName'] ?? $dva['bankType'] ?? 'GTBank',
                'amount'         => $validated['amount'],
                'total_amount'   => (int) ($dva['totalAmount'] ?? $amountKobo) / 100,
                'duration'       => $dva['duration'] ?? 2880,
            ]);

        } catch (\Throwable $e) {
            Log::error('ZainPay payment init failed: ' . $e->getMessage());

            return response()->json(['message' => 'Payment initialization failed. Please try again.'], 500);
        }
    }

    public function showReceipt(string $slug, string $paymentRef)
    {
        $guestPayment = GuestPayment::with('collection')
            ->where('payment_reference', $paymentRef)
            ->firstOrFail();

        $receiptData = [
            'ref'        => $guestPayment->payment_reference,
            'amount'     => $guestPayment->amount,
            'collection' => $guestPayment->collection->name,
            'date'       => $guestPayment->completed_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
        ];

        return Inertia::render('guest/Receipt', [
            'payment' => [
                'id'                    => $guestPayment->id,
                'payment_reference'     => $guestPayment->payment_reference,
                'transaction_reference' => $guestPayment->transaction_reference,
                'customer_name'         => $guestPayment->customer_name,
                'amount'                => $guestPayment->amount,
                'is_anonymous'          => $guestPayment->is_anonymous,
                'payment_type'          => $guestPayment->payment_type,
                'status'                => $guestPayment->status,
                'completed_at'          => $guestPayment->completed_at?->format('F j, Y g:i A'),
            ],
            'collection' => [
                'id'         => $guestPayment->collection->id,
                'name'       => $guestPayment->collection->name,
                'slug'       => $slug,
                'icon'       => $guestPayment->collection->icon ?? 'group',
                'owner_name' => $guestPayment->collection->owner->name ?? 'Unknown',
            ],
            'qr_data' => json_encode($receiptData),
            'appUrl'  => config('app.url'),
        ]);
    }

    /**
     * Poll payment status — called by the receipt page every few seconds.
     * Returns {status, confirmed} so the frontend can update without a full page reload.
     */
    public function checkPaymentStatus(string $paymentRef): \Illuminate\Http\JsonResponse
    {
        $guestPayment = GuestPayment::where('payment_reference', $paymentRef)->first();

        if (! $guestPayment) {
            return response()->json(['status' => 'not_found', 'confirmed' => false], 404);
        }

        if ($guestPayment->status === 'completed') {
            return response()->json(['status' => 'completed', 'confirmed' => true]);
        }

        if ($guestPayment->status === 'failed') {
            return response()->json(['status' => 'failed', 'confirmed' => false]);
        }

        // Still pending — use the DVA-specific endpoint (scoped to this txnRef only)
        try {
            $dva = $this->zainpay->getDvaStatus($paymentRef);

            if ($dva) {
                $dvaStatus = strtolower($dva['status'] ?? $dva['txnStatus'] ?? '');

                // ZainPay DVA amounts are in kobo; guestPayment->amount is in naira
                $dvaKobo  = (int) ($dva['amount'] ?? $dva['totalAmount'] ?? 0);
                $dvaNaira = $dvaKobo >= 100 ? (int) round($dvaKobo / 100) : $dvaKobo;

                if (in_array($dvaStatus, ['success', 'successful', 'completed', 'paid'])
                    && $dvaNaira >= (int) $guestPayment->amount) {
                    $this->processSuccessfulPayment($guestPayment, $dva);
                    return response()->json(['status' => 'completed', 'confirmed' => true]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Payment status check failed: ' . $e->getMessage(), ['ref' => $paymentRef]);
        }

        return response()->json(['status' => 'pending', 'confirmed' => false]);
    }

    /**
     * Verify a ZainPay card-payment callback and decide where the payer goes next.
     * Returns ['status' => success|pending|failed, 'redirect' => url, 'message' => ?string].
     */
    private function resolveZainPayCallback(Request $request, string $slug): array
    {
        // ZainPay appends ?txnRef=... to the callBackUrl we provided
        $txnRef = $request->query('txnRef')
            ?? $request->query('tx_ref')
            ?? $request->query('ref');

        Log::info('ZainPay callback', [
            'slug'   => $slug,
            'txnRef' => $txnRef,
            'url'    => $request->fullUrl(),
        ]);

        $collectionUrl = route('collections.guest', ['slug' => $slug]);

        if (! $txnRef) {
            return ['status' => 'failed', 'redirect' => $collectionUrl, 'message' => 'Payment reference missing. Please try again.'];
        }

        $guestPayment = GuestPayment::where('payment_reference', $txnRef)->first();

        if (! $guestPayment) {
            return ['status' => 'failed', 'redirect' => $collectionUrl, 'message' => 'Payment not found. Please try again.'];
        }

        $receiptUrl = route('collections.guest.receipt', [
            'slug'       => $slug,
            'paymentRef' => $guestPayment->payment_reference,
        ]);

        if ($guestPayment->status === 'completed') {
            return ['status' => 'success', 'redirect' => $receiptUrl, 'message' => null];
        }

        if ($guestPayment->status !== 'pending') {
            return ['status' => 'failed', 'redirect' => $collectionUrl, 'message' => 'Payment was not completed. Please try again.'];
        }

        $transaction = $this->zainpay->verifyDeposit($txnRef);

        if ($transaction) {
            $txStatus  = strtolower($transaction['txnStatus'] ?? $transaction['status'] ?? '');
            $txKobo    = (int) ($transaction['amountAfterCharges'] ?? $transaction['amount'] ?? 0);
            $txNaira   = $txKobo >= 100 ? (int) round($txKobo / 100) : $txKobo;
            $txAmount  = max($txKobo, $txNaira); // accept either unit

            if (in_array($txStatus, ['success', 'successful', 'completed']) && $txAmount >= (int) $guestPayment->amount) {
                $this->processSuccessfulPayment($guestPayment, $transaction);

                return ['status' => 'success', 'redirect' => $receiptUrl, 'message' => null];
            }

            // ZainPay confirmed the transaction exists but it did not succeed
            $guestPayment->update(['status' => 'failed']);

            Log::warning('ZainPay callback: payment verified as failed', ['txnRef' => $txnRef, 'status' => $txStatus]);

            return ['status' => 'failed', 'redirect' => $collectionUrl, 'message' => 'Payment could not be verified. Contact support if you were charged.'];
        }

        // Could not verify at all (endpoint unreachable) — don't fail a payment that
        // may have succeeded; the receipt page shows pending and the webhook confirms.
        Log::warning('ZainPay callback: verification unavailable, leaving payment pending', ['txnRef' => $txnRef]);

        return ['status' => 'pending', 'redirect' => $receiptUrl, 'message' => null];
    }

    /**
     * Handle ZainPay redirect callback after card payment (full-page checkout).
     */
    public function handleZainPayCallback(Request $request, string $slug): RedirectResponse
    {
        $outcome = $this->resolveZainPayCallback($request, $slug);

        $redirect = redirect()->to($outcome['redirect']);

        return $outcome['message'] ? $redirect->with('error', $outcome['message']) : $redirect;
    }

    /**
     * Callback for checkout running inside the in-app iframe modal: renders a
     * tiny page that notifies the parent window and breaks out of the frame.
     */
    public function handleZainPayCallbackEmbedded(Request $request, string $slug)
    {
        $outcome = $this->resolveZainPayCallback($request, $slug);

        return response()
            ->view('guest.embedded-callback', $outcome)
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    /**
     * Handle ZainPay webhook (deposits and transfer completions).
     */
    public function handleZainPayWebhook(Request $request)
    {
        if (! $this->zainpay->verifyWebhookSignature($request)) {
            Log::warning('Rejected ZainPay webhook: invalid signature.');
            return response()->json(['status' => 'invalid signature'], 401);
        }

        $payload = $request->all();
        $event = strtolower(
            $payload['event']     ??
            $payload['type']      ??
            $payload['eventType'] ??
            $payload['eventName'] ??
            ''
        );

        Log::info('ZainPay webhook received', ['event' => $event, 'payload' => $payload]);

        // Detect withdrawal/transfer events — ZainPay uses various naming conventions
        $isTransferEvent = str_contains($event, 'transfer')
            || str_contains($event, 'withdrawal')
            || str_contains($event, 'payout')
            || str_contains($event, 'debit');

        // Also detect by txnRef prefix — withdrawal refs always start with GATHR_WD_
        if (! $isTransferEvent) {
            $data   = $payload['data'] ?? $payload;
            $txnRef = $data['txnRef'] ?? $data['transactionRef'] ?? $data['reference'] ?? '';
            if (str_starts_with((string) $txnRef, 'GATHR_WD_')) {
                $isTransferEvent = true;
            }
        }

        if ($isTransferEvent) {
            $this->processWithdrawalWebhook($payload, $event);
            return response()->json(['status' => 'success']);
        }

        // Extract txnRef from every possible location ZainPay may use
        $data   = $payload['data'] ?? $payload;
        $txnRef = $data['txnRef']          // most common
            ?? $data['transactionRef']      // seen in zainbox/transactions
            ?? $data['txn_ref']
            ?? $data['reference']
            ?? $data['paymentRef']
            ?? $payload['txnRef']
            ?? $payload['reference']
            ?? null;

        // Fallback: scan narration for our GATHR_ reference pattern
        if (! $txnRef) {
            $narration = $data['narration'] ?? $data['description'] ?? '';
            if (preg_match('/GATHR_\d+_[A-Z0-9]+/', $narration, $matches)) {
                $txnRef = $matches[0];
            }
        }

        if (! $txnRef) {
            Log::warning('ZainPay webhook: could not extract txnRef', ['payload' => $payload]);
            return response()->json(['status' => 'success']); // ack to stop retries
        }

        // Only process our own references
        if (! str_starts_with($txnRef, 'GATHR_')) {
            return response()->json(['status' => 'success']);
        }

        $guestPayment = GuestPayment::where('payment_reference', $txnRef)->first();

        if (! $guestPayment || $guestPayment->status !== 'pending') {
            return response()->json(['status' => 'success']);
        }

        $failStatuses = ['failed', 'cancelled', 'mismatch', 'expired', 'reversed'];
        $txStatus     = strtolower($data['status'] ?? $data['txnStatus'] ?? $data['transactionType'] ?? '');

        if (in_array($txStatus, $failStatuses)) {
            $guestPayment->update(['status' => 'failed']);
            return response()->json(['status' => 'success']);
        }

        // Always verify with ZainPay directly — don't trust webhook payload status alone
        try {
            $verified = $this->zainpay->verifyDeposit($txnRef);
            if ($verified) {
                $this->processSuccessfulPayment($guestPayment, $verified);
            }
        } catch (\Throwable $e) {
            Log::error('ZainPay webhook verification failed', ['txnRef' => $txnRef, 'error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    private function processSuccessfulPayment(GuestPayment $guestPayment, array $transactionData): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($guestPayment, $transactionData) {
            // Re-fetch inside transaction with lock to prevent double-processing
            $locked = GuestPayment::lockForUpdate()->find($guestPayment->id);

            if (! $locked || $locked->status !== 'pending') {
                return; // already processed by a concurrent webhook/poll
            }

            $locked->update(['status' => 'completed', 'completed_at' => now()]);

            $netAmount = $locked->amount - ($locked->fees ?? 0);

            CollectionPayment::create([
                'collection_id' => $locked->collection_id,
                'user_id'       => null,
                'customer_name' => $locked->is_anonymous ? null : $locked->customer_name,
                'amount'        => $netAmount,
                'fees'          => $locked->fees ?? 0,
                'note'          => $locked->is_anonymous
                    ? 'Anonymous payment (Ref: ' . $locked->transaction_reference . ')'
                    : "Payment by {$locked->customer_name} (Ref: {$locked->transaction_reference})",
                'paid_at' => now(),
            ]);

            Log::info('Payment confirmed and credited', [
                'collection_id' => $locked->collection_id,
                'ref'           => $locked->payment_reference,
                'amount'        => $netAmount,
            ]);
        });
    }

    private function processWithdrawalWebhook(array $payload, string $event = ''): void
    {
        $data = $payload['data'] ?? $payload;

        // ZainPay uses inconsistent field names across transfer webhook types
        $reference = $data['txnRef']
            ?? $data['transactionRef']
            ?? $data['txn_ref']
            ?? $data['reference']
            ?? $data['paymentRef']
            ?? $payload['txnRef']
            ?? $payload['reference']
            ?? null;

        if (! $reference) {
            Log::warning('ZainPay withdrawal webhook missing reference.', ['payload' => $payload]);
            return;
        }

        // ZainPay's real transfer webhook carries NO explicit status field — success/failure
        // is encoded in the event name ("transfer.success" / "transfer.failed"). Fall back to
        // any status-like field for other payload shapes.
        $status = strtolower((string) (
            $data['status']          ??
            $data['txnStatus']       ??
            $data['transactionType'] ??
            $data['txnType']         ??
            $data['state']           ??
            ''
        ));

        if (str_contains($event, 'success')) {
            $status = 'success';
        } elseif (str_contains($event, 'fail') || str_contains($event, 'reverse') || str_contains($event, 'declin')) {
            $status = 'failed';
        }

        $failStatuses    = ['failed', 'cancelled', 'reversed', 'rejected', 'error'];
        $successStatuses = ['success', 'successful', 'completed', 'transferred', 'debit', 'transfer'];

        // Lock the row before reading status to prevent two simultaneous webhook
        // deliveries from both passing the 'completed' guard and double-updating.
        DB::transaction(function () use ($reference, $status, $failStatuses, $successStatuses, $data) {
            $withdrawal = Withdrawal::lockForUpdate()
                ->where('transaction_reference', $reference)
                ->first();

            if (! $withdrawal) {
                Log::warning('ZainPay withdrawal webhook: no matching record.', ['reference' => $reference]);
                return;
            }

            if ($withdrawal->status === 'completed') {
                return; // already processed — idempotent exit
            }

            if (in_array($status, $successStatuses)) {
                $withdrawal->update([
                    'status'         => 'completed',
                    'processed_at'   => now(),
                    'failure_reason' => null,
                ]);
                Log::info('ZainPay withdrawal completed via webhook.', [
                    'withdrawal_id' => $withdrawal->id,
                    'reference'     => $reference,
                ]);
                return;
            }

            if (in_array($status, $failStatuses)) {
                $withdrawal->update([
                    'status'         => 'failed',
                    'failure_reason' => $data['description'] ?? $data['message'] ?? $data['failureReason'] ?? 'ZainPay transfer failed.',
                    'processed_at'   => now(),
                ]);
                Log::warning('ZainPay withdrawal failed via webhook.', [
                    'withdrawal_id' => $withdrawal->id,
                    'reference'     => $reference,
                    'status'        => $status,
                ]);
            }
        });
    }
}
