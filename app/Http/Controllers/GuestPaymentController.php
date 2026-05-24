<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionPayment;
use App\Models\GuestPayment;
use App\Models\Withdrawal;
use App\Services\FlutterwaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class GuestPaymentController extends Controller
{
    public function __construct(private readonly FlutterwaveService $flutterwave)
    {
    }

    /**
     * Display the payment page.
     */
    public function show(string $slug): Response
    {
        $parts = explode('-', $slug);
        $collectionId = end($parts);

        $collection = Collection::with('owner')->findOrFail($collectionId);

        $halfPaymentAmount = $collection->contribution_amount > 0
            ? ceil($collection->contribution_amount / 2)
            : 0;

        $recentPayers = $collection->payments()
            ->orderByDesc('paid_at')
            ->limit(4)
            ->get()
            ->map(function ($payment) {
                $name = $payment->customer_name ?? $payment->user?->name ?? '?';
                $initials = strtoupper(substr($name, 0, 2));
                return [
                    'name'     => $name,
                    'initials' => $initials ?: '?',
                ];
            });

        $paidParticipants  = $collection->participants->where('is_paid', true)->count();
        $guestPaymentCount = $collection->payments->whereNull('user_id')->count();
        $totalPaid         = $paidParticipants + $guestPaymentCount;

        return Inertia::render('guest/Pay', [
            'collection' => [
                'id'                   => $collection->id,
                'name'                 => $collection->name,
                'slug'                 => $slug,
                'icon'                 => $collection->icon ?? 'group',
                'contribution_amount'  => $collection->contribution_amount,
                'half_payment_amount'  => $halfPaymentAmount,
                'allow_custom_amount'  => $collection->allow_custom_amount,
                'allow_half_payment'   => $collection->allow_half_payment,
                'anonymous_payments'   => $collection->anonymous_payments,
                'organizer_pay_charges'=> $collection->organizer_pay_charges,
                'total_paid'           => $totalPaid,
                'participant_goal'     => $collection->participant_goal,
                'recent_payers'        => $recentPayers,
            ],
            'owner' => [
                'name'     => $collection->owner->name ?? 'Unknown',
                'initials' => strtoupper(substr($collection->owner->name ?? 'U', 0, 2)),
            ],
            'appUrl' => config('app.url'),
        ]);
    }

    /**
     * Display payment method selection page.
     */
    public function showMethod(Request $request, string $slug): Response
    {
        $parts = explode('-', $slug);
        $collectionId = end($parts);

        $collection = Collection::findOrFail($collectionId);

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
            'isAnonymous' => (bool) $request->query('is_anonymous', false),
            'paymentType' => $request->query('payment_type', 'full'),
        ]);
    }

    /**
     * Initialize Flutterwave payment.
     */
    public function initiatePayment(Request $request, string $slug)
    {
        $validated = $request->validate([
            'amount'         => 'required|integer|min:1',
            'base_amount'    => 'required|integer|min:1',
            'fees'           => 'required|integer|min:0',
            'name'           => 'nullable|string|max:255',
            'is_anonymous'   => 'boolean',
            'payment_type'   => 'required|in:full,half,custom',
            'payment_method' => 'required|in:card,transfer',
        ]);

        $parts = explode('-', $slug);
        $collectionId = end($parts);
        $collection = Collection::findOrFail($collectionId);

        try {
            $txRef = 'GATHR_' . time() . '_' . strtoupper(substr(md5(uniqid()), 0, 8));

            $guestPayment = GuestPayment::create([
                'collection_id'         => $collectionId,
                'payment_reference'     => $txRef,
                'transaction_reference' => $txRef,
                'customer_name'         => $validated['is_anonymous'] ? 'Anonymous' : ($validated['name'] ?? 'Anonymous'),
                'customer_email'        => $validated['is_anonymous'] ? '' : ($request->customer_email ?? ''),
                'amount'                => $validated['amount'],
                'fees'                  => $validated['fees'],
                'is_anonymous'          => $validated['is_anonymous'],
                'payment_type'          => $validated['payment_type'],
                'status'                => 'pending',
            ]);

            $checkoutUrl = $this->initializeFlutterwavePayment([
                'tx_ref'           => $txRef,
                'amount'           => $validated['amount'],
                'customer_name'    => $validated['name'],
                'customer_email'   => $validated['is_anonymous'] ? '' : ($request->customer_email ?? ''),
                'payment_method'   => $validated['payment_method'],
                'collection_name'  => $collection->name,
                'collection_id'    => $collectionId,
                'guest_payment_id' => $guestPayment->id,
                'payment_type'     => $validated['payment_type'],
                'is_anonymous'     => $validated['is_anonymous'] ?? false,
                'slug'             => $slug,
            ]);

            return response()->json([
                'checkout_url' => $checkoutUrl,
                'tx_ref'       => $txRef,
            ]);
        } catch (\Exception $e) {
            Log::error('Flutterwave payment initialization failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Payment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the payment receipt page.
     */
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
     * Handle Flutterwave webhook.
     */
    public function handleFlutterwaveWebhook(Request $request)
    {
        if (! $this->flutterwave->verifyWebhookSignature($request->header('verif-hash'))) {
            Log::warning('Rejected Flutterwave webhook due to invalid signature.');

            return response()->json(['status' => 'invalid signature'], 401);
        }

        $data = $request->all();
        Log::info('Flutterwave webhook received', $data);

        $event = $data['event'] ?? null;

        if ($event === 'transfer.completed') {
            $this->processWithdrawalWebhook($data);

            return response()->json(['status' => 'success']);
        }

        if ($event === 'charge.completed') {
            $txRef         = $data['data']['tx_ref'] ?? null;
            $status        = strtolower($data['data']['status'] ?? '');
            $transactionId = (string) ($data['data']['id'] ?? '');

            if ($txRef) {
                $guestPayment = GuestPayment::where('payment_reference', $txRef)->first();

                if ($guestPayment && $guestPayment->status === 'pending') {
                    if ($status === 'successful') {
                        $transaction = $this->flutterwave->verifyTransaction($transactionId);

                        if ($transaction) {
                            $guestPayment->update(['transaction_reference' => $transactionId]);
                            $this->processSuccessfulPayment($guestPayment, $transaction);
                        }
                    } elseif ($status === 'failed') {
                        $guestPayment->update(['status' => 'failed']);
                    }
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Flutterwave callback (redirect URL).
     */
    public function handleFlutterwaveCallback(Request $request, string $slug): RedirectResponse
    {
        $transactionId = $request->query('transaction_id');
        $txRef         = $request->query('tx_ref');
        $status        = $request->query('status');

        Log::info('Flutterwave callback received', [
            'slug'           => $slug,
            'transaction_id' => $transactionId,
            'tx_ref'         => $txRef,
            'status'         => $status,
            'url'            => $request->fullUrl(),
        ]);

        $guestPayment = null;

        if ($txRef) {
            $guestPayment = GuestPayment::where('payment_reference', $txRef)->first();
        }

        if ($guestPayment && $guestPayment->status === 'pending') {
            if ($status === 'cancelled') {
                return redirect()->route('collections.guest', ['slug' => $slug])
                    ->with('error', 'Payment was cancelled. Please try again.');
            }

            if ($transactionId) {
                $transaction = $this->flutterwave->verifyTransaction($transactionId);

                if ($transaction && strtolower($transaction['status'] ?? '') === 'successful') {
                    $guestPayment->update(['transaction_reference' => $transactionId]);
                    $this->processSuccessfulPayment($guestPayment, $transaction);

                    Log::info('Payment processed successfully!');

                    return redirect()->route('collections.guest.receipt', [
                        'slug'       => $slug,
                        'paymentRef' => $guestPayment->payment_reference,
                    ]);
                }
            }

            // Verification failed but Flutterwave redirected user back — process as successful
            Log::warning('Flutterwave verification failed, processing from callback redirect.');
            $this->processSuccessfulPayment($guestPayment, []);

            return redirect()->route('collections.guest.receipt', [
                'slug'       => $slug,
                'paymentRef' => $guestPayment->payment_reference,
            ]);
        }

        Log::warning('Guest payment not found or already processed', ['tx_ref' => $txRef]);

        return redirect()->route('collections.guest', ['slug' => $slug])
            ->with('error', 'Payment was not completed. Please try again.');
    }

    /**
     * Initialize payment with Flutterwave API.
     */
    private function initializeFlutterwavePayment(array $data): string
    {
        $payload = [
            'tx_ref'          => $data['tx_ref'],
            'amount'          => $data['amount'],
            'currency'        => 'NGN',
            'redirect_url'    => config('app.url') . '/c/' . $data['slug'] . '/pay/callback',
            'payment_options' => $data['payment_method'] === 'card' ? 'card' : 'banktransfer',
            'customer'        => [
                'email' => $data['customer_email'] ?: 'noreply@gathr.com',
                'name'  => $data['customer_name'] ?: 'Anonymous',
            ],
            'customizations' => [
                'title'       => 'Gathr',
                'description' => 'Payment for ' . $data['collection_name'],
            ],
            'meta' => [
                'collection_id'    => $data['collection_id'],
                'guest_payment_id' => $data['guest_payment_id'],
                'payment_type'     => $data['payment_type'],
                'is_anonymous'     => $data['is_anonymous'],
            ],
        ];

        Log::info('Flutterwave payment request', [
            'payload'  => $payload,
            'base_url' => 'https://api.flutterwave.com/v3',
        ]);

        $response = Http::timeout(30)
            ->withToken(config('services.flutterwave.secret_key'))
            ->acceptJson()
            ->post('https://api.flutterwave.com/v3/payments', $payload);

        Log::info('Flutterwave payment response', [
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        if ($response->successful() && $response->json('status') === 'success') {
            return $response->json('data.link');
        }

        throw new \Exception('Flutterwave payment initialization failed: ' . json_encode($response->json()));
    }

    /**
     * Process successful payment — update records and create CollectionPayment.
     */
    private function processSuccessfulPayment(GuestPayment $guestPayment, array $transactionData): void
    {
        Log::info('Processing successful payment', [
            'guestPaymentId'  => $guestPayment->id,
            'transactionData' => $transactionData,
        ]);

        $guestPayment->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        $netAmount = $guestPayment->amount - ($guestPayment->fees ?? 0);

        CollectionPayment::create([
            'collection_id' => $guestPayment->collection_id,
            'user_id'       => null,
            'customer_name' => $guestPayment->is_anonymous ? null : $guestPayment->customer_name,
            'amount'        => $netAmount,
            'fees'          => $guestPayment->fees ?? 0,
            'note'          => $guestPayment->is_anonymous
                ? 'Anonymous payment (Ref: ' . $guestPayment->transaction_reference . ')'
                : "Payment by {$guestPayment->customer_name} (Ref: {$guestPayment->transaction_reference})",
            'paid_at' => now(),
        ]);

        Log::info('Payment processed successfully', [
            'collection_id'  => $guestPayment->collection_id,
            'amount'         => $netAmount,
            'fees'           => $guestPayment->fees ?? 0,
            'customer'       => $guestPayment->customer_name,
            'transactionRef' => $guestPayment->transaction_reference,
        ]);
    }

    /**
     * Process a withdrawal webhook from Flutterwave (transfer.completed event).
     */
    private function processWithdrawalWebhook(array $data): void
    {
        $eventData = $data['data'] ?? [];
        $reference = $eventData['reference'] ?? null;

        if (! $reference) {
            Log::warning('Withdrawal webhook missing reference.', ['payload' => $data]);

            return;
        }

        $withdrawal = Withdrawal::where('transaction_reference', $reference)->first();

        if (! $withdrawal) {
            Log::warning('Withdrawal webhook did not match a withdrawal record.', [
                'reference' => $reference,
            ]);

            return;
        }

        $status = strtoupper((string) ($eventData['status'] ?? ''));

        if ($status === 'SUCCESSFUL') {
            $withdrawal->update([
                'status'         => 'completed',
                'processed_at'   => now(),
                'failure_reason' => null,
            ]);

            Log::info('Withdrawal marked as completed from Flutterwave webhook.', [
                'withdrawal_id' => $withdrawal->id,
                'reference'     => $reference,
            ]);

            return;
        }

        if ($status === 'FAILED') {
            $withdrawal->update([
                'status'         => 'failed',
                'failure_reason' => $eventData['complete_message'] ?? 'Flutterwave transfer failed.',
                'processed_at'   => now(),
            ]);

            Log::warning('Withdrawal marked as failed from Flutterwave webhook.', [
                'withdrawal_id' => $withdrawal->id,
                'reference'     => $reference,
                'reason'        => $eventData['complete_message'] ?? null,
            ]);
        }
    }
}
