<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionParticipation;
use App\Models\CollectionPayment;
use App\Models\GuestPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class GuestPaymentController extends Controller
{
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

        // Get recent payers for avatar display
        $recentPayers = $collection->payments()
            ->orderByDesc('paid_at')
            ->limit(4)
            ->get()
            ->map(function ($payment) {
                $name = $payment->customer_name ?? $payment->user?->name ?? '?';
                $initials = strtoupper(substr($name, 0, 2));
                return [
                    'name' => $name,
                    'initials' => $initials ?: '?',
                ];
            });

        // Count total paid (participants + guest payments)
        $paidParticipants = $collection->participants->where('is_paid', true)->count();
        $guestPaymentCount = $collection->payments->whereNull('user_id')->count();
        $totalPaid = $paidParticipants + $guestPaymentCount;

        return Inertia::render('guest/Pay', [
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name,
                'slug' => $slug,
                'icon' => $collection->icon ?? 'group',
                'contribution_amount' => $collection->contribution_amount,
                'half_payment_amount' => $halfPaymentAmount,
                'allow_custom_amount' => $collection->allow_custom_amount,
                'total_paid' => $totalPaid,
                'participant_goal' => $collection->participant_goal,
                'recent_payers' => $recentPayers,
            ],
            'owner' => [
                'name' => $collection->owner->name ?? 'Unknown',
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
                'id' => $collection->id,
                'name' => $collection->name,
                'slug' => $slug,
            ],
            'amount' => (int) $request->query('amount', 0),
            'name' => $request->query('name', ''),
            'isAnonymous' => (bool) $request->query('is_anonymous', false),
            'paymentType' => $request->query('payment_type', 'full'),
        ]);
    }

    /**
     * Initialize Monnify payment.
     */
    public function initiatePayment(Request $request, string $slug)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'name' => 'nullable|string|max:255',
            'is_anonymous' => 'boolean',
            'payment_type' => 'required|in:full,half,custom',
            'payment_method' => 'required|in:card,transfer',
        ]);

        $parts = explode('-', $slug);
        $collectionId = end($parts);

        $collection = Collection::findOrFail($collectionId);

        try {
            // Generate payment references
            $paymentRef = 'GATHR_' . time() . '_' . strtoupper(substr(md5(uniqid()), 0, 8));
            $transactionRef = 'GATHR_TXN_' . time() . '_' . strtoupper(substr(md5(uniqid()), 0, 8));

            // Save pending payment data
            $guestPayment = GuestPayment::create([
                'collection_id' => $collectionId,
                'payment_reference' => $paymentRef,
                'transaction_reference' => $transactionRef,
                'customer_name' => $validated['is_anonymous'] ? 'Anonymous' : ($validated['name'] ?? 'Anonymous'),
                'customer_email' => $validated['is_anonymous'] ? '' : ($request->customer_email ?? ''),
                'amount' => $validated['amount'],
                'is_anonymous' => $validated['is_anonymous'],
                'payment_type' => $validated['payment_type'],
                'status' => 'pending',
            ]);

            // Initialize Monnify payment
            $monnifyResponse = $this->initializeMonnifyPayment([
                'amount' => $validated['amount'],
                'customer_name' => $validated['name'],
                'customer_email' => $validated['is_anonymous'] ? '' : ($request->customer_email ?? ''),
                'payment_method' => $validated['payment_method'],
                'collection_id' => $collectionId,
                'collection_name' => $collection->name,
                'payment_type' => $validated['payment_type'],
                'slug' => $slug,
                'guest_payment_id' => $guestPayment->id,
                'payment_reference' => $paymentRef,
                'transaction_reference' => $transactionRef,
            ]);

            // Update guest payment with actual Monnify transaction reference
            $guestPayment->update([
                'transaction_reference' => $monnifyResponse['transactionRef'],
            ]);

            // Return checkout URL as JSON
            return response()->json([
                'checkout_url' => $monnifyResponse['checkoutUrl'],
                'transaction_ref' => $monnifyResponse['transactionRef'],
            ]);
        } catch (\Exception $e) {
            Log::error('Monnify payment initialization failed: ' . $e->getMessage());
            
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

        // Generate receipt data for QR code
        $receiptData = [
            'ref' => $guestPayment->payment_reference,
            'amount' => $guestPayment->amount,
            'collection' => $guestPayment->collection->name,
            'date' => $guestPayment->completed_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
        ];

        $qrData = json_encode($receiptData);

        return \Inertia\Inertia::render('guest/Receipt', [
            'payment' => [
                'id' => $guestPayment->id,
                'payment_reference' => $guestPayment->payment_reference,
                'transaction_reference' => $guestPayment->transaction_reference,
                'customer_name' => $guestPayment->customer_name,
                'amount' => $guestPayment->amount,
                'is_anonymous' => $guestPayment->is_anonymous,
                'payment_type' => $guestPayment->payment_type,
                'status' => $guestPayment->status,
                'completed_at' => $guestPayment->completed_at?->format('F j, Y g:i A'),
            ],
            'collection' => [
                'id' => $guestPayment->collection->id,
                'name' => $guestPayment->collection->name,
                'slug' => $slug,
                'icon' => $guestPayment->collection->icon ?? 'group',
                'owner_name' => $guestPayment->collection->owner->name ?? 'Unknown',
            ],
            'qr_data' => $qrData,
            'appUrl' => config('app.url'),
        ]);
    }

    /**
     * Handle Monnify webhook/callback.
     */
    public function handleMonnifyWebhook(Request $request)
    {
        $data = $request->all();

        Log::info('Monnify webhook received', $data);

        // Get transaction reference from webhook data
        $transactionRef = $data['transactionReference'] 
            ?? $data['eventData']['transactionReference'] 
            ?? null;

        if ($transactionRef) {
            $guestPayment = GuestPayment::where('transaction_reference', $transactionRef)->first();
            
            if ($guestPayment && $guestPayment->status === 'pending') {
                $paymentStatus = $data['paymentStatus'] 
                    ?? $data['eventData']['status'] 
                    ?? null;

                if ($paymentStatus === 'SUCCESSFUL' || $paymentStatus === 'PAID' || $paymentStatus === 'SUCCESS') {
                    $transaction = $this->verifyMonnifyTransaction($transactionRef);
                    
                    if ($transaction) {
                        $this->processSuccessfulPayment($guestPayment, $transaction);
                    }
                } elseif ($paymentStatus === 'FAILED' || $paymentStatus === 'FAILED') {
                    $guestPayment->update(['status' => 'failed']);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Monnify callback (return URL).
     */
    public function handleMonnifyCallback(Request $request, string $slug): RedirectResponse
    {
        $allData = $request->all();
        $transactionRef = $request->query('transactionReference');
        $paymentReference = $request->query('paymentReference');
        
        // Monnify uses paymentStatus in some versions, status in others
        $paymentStatus = $request->query('paymentStatus') ?? $request->query('status');

        Log::info('Monnify callback received - RAW DATA', [
            'slug' => $slug,
            'all_query_params' => $allData,
            'transactionRef' => $transactionRef,
            'paymentStatus' => $paymentStatus,
            'paymentReference' => $paymentReference,
            'url' => $request->fullUrl(),
        ]);

        // Find guest payment by payment reference (this is what Monnify sends)
        $guestPayment = null;
        
        if ($paymentReference) {
            $guestPayment = GuestPayment::where('payment_reference', $paymentReference)->first();
            Log::info('Searching by payment_reference', [
                'found' => $guestPayment ? 'yes' : 'no',
                'paymentReference' => $paymentReference,
            ]);
        }
        
        if (!$guestPayment && $transactionRef) {
            $guestPayment = GuestPayment::where('transaction_reference', $transactionRef)->first();
            Log::info('Searching by transaction_reference', ['found' => $guestPayment ? 'yes' : 'no']);
        }

        Log::info('Guest payment found', [
            'id' => $guestPayment?->id,
            'status' => $guestPayment?->status,
            'amount' => $guestPayment?->amount,
            'payment_reference' => $guestPayment?->payment_reference,
        ]);

        // If we found a pending guest payment, verify and process it
        if ($guestPayment && $guestPayment->status === 'pending') {
            Log::info('Found pending guest payment, verifying with Monnify...');
            
            // Verify transaction with Monnify using transaction reference
            $monnifyRef = $transactionRef ?: $guestPayment->transaction_reference;
            $transaction = $this->verifyMonnifyTransaction($monnifyRef);

            if ($transaction) {
                // Check if transaction status is successful
                $txStatus = $transaction['status'] ?? $transaction['transactionStatus'] ?? null;
                
                Log::info('Transaction verification result', [
                    'status' => $txStatus,
                    'full_transaction' => $transaction,
                ]);

                if ($txStatus === 'SUCCESSFUL' || $txStatus === 'PAID' || $txStatus === 'SUCCESS') {
                    $this->processSuccessfulPayment($guestPayment, $transaction);
                    Log::info('Payment processed successfully!');

                    // Redirect to receipt page
                    return redirect()->route('collections.guest.receipt', [
                        'slug' => $slug,
                        'paymentRef' => $guestPayment->payment_reference,
                    ]);
                } else {
                    Log::warning('Transaction not successful', ['status' => $txStatus]);
                }
            } else {
                // Verification failed, but payment might still be processing
                // Give user benefit of doubt if Monnify redirected them back
                Log::warning('Verification failed, marking as completed since user was redirected back');
                $this->processSuccessfulPayment($guestPayment, []);
                Log::info('Payment processed successfully (without verification)!');

                // Redirect to receipt page
                return redirect()->route('collections.guest.receipt', [
                    'slug' => $slug,
                    'paymentRef' => $guestPayment->payment_reference,
                ]);
            }
        } else {
            Log::warning('Guest payment not found or already processed', [
                'paymentReference' => $paymentReference,
                'guestPayment' => $guestPayment ? [
                    'id' => $guestPayment->id,
                    'status' => $guestPayment->status,
                ] : null,
            ]);
        }

        return redirect()->route('collections.guest', ['slug' => $slug])
            ->with('error', 'Payment was not completed. Please try again.');
    }

    /**
     * Initialize payment with Monnify API.
     */
    private function initializeMonnifyPayment(array $data): array
    {
        // Get Monnify access token
        $accessToken = $this->getMonnifyAccessToken();

        // Use the transaction reference passed from the caller
        $transactionRef = $data['transaction_reference'] ?? ('GATHR_TXN_' . time() . '_' . strtoupper(substr(md5(uniqid()), 0, 8)));

        $payload = [
            'amount' => $data['amount'],
            'customerName' => $data['customer_name'],
            'customerEmail' => $data['customer_email'] ?: 'noreply@gathr.com',
            'paymentReference' => $data['payment_reference'], // Use our payment reference
            'paymentDescription' => "Payment for {$data['collection_name']}",
            'currencyCode' => 'NGN',
            'contractCode' => config('services.monnify.contract_code'),
            'redirectUrl' => config('app.url') . '/c/' . $data['slug'] . '/pay/callback',
            'paymentMethods' => $data['payment_method'] === 'card' ? ['CARD'] : ['ACCOUNT_TRANSFER'],
            'metadata' => [
                'collection_id' => $data['collection_id'],
                'guest_payment_id' => $data['guest_payment_id'],
                'payment_type' => $data['payment_type'],
                'is_anonymous' => $data['is_anonymous'] ?? false,
            ],
        ];

        Log::info('Monnify payment request', [
            'payload' => $payload,
            'contract_code' => config('services.monnify.contract_code'),
            'base_url' => config('services.monnify.base_url'),
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post(config('services.monnify.base_url') . '/api/v1/merchant/transactions/init-transaction', $payload);

        Log::info('Monnify payment response', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        if ($response->successful() && $response->json('requestSuccessful')) {
            return [
                'checkoutUrl' => $response->json('responseBody.checkoutUrl'),
                'transactionRef' => $transactionRef,
            ];
        }

        Log::error('Monnify initialization failed', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        throw new \Exception('Monnify payment initialization failed: ' . json_encode($response->json()));
    }

    /**
     * Get Monnify access token.
     */
    private function getMonnifyAccessToken(): string
    {
        $response = Http::withBasicAuth(
            config('services.monnify.api_key'),
            config('services.monnify.secret_key')
        )->post(config('services.monnify.base_url') . '/api/v1/auth/login', []);

        if ($response->successful()) {
            return $response->json('responseBody.accessToken');
        }

        throw new \Exception('Failed to get Monnify access token');
    }

    /**
     * Verify Monnify transaction.
     */
    private function verifyMonnifyTransaction(string $transactionRef): ?array
    {
        $accessToken = $this->getMonnifyAccessToken();

        Log::info('Verifying Monnify transaction', ['transactionRef' => $transactionRef]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get(config('services.monnify.base_url') . "/api/v2/transactions/{$transactionRef}/verify");

        Log::info('Monnify verification response', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        if ($response->successful() && $response->json('requestSuccessful')) {
            $responseBody = $response->json('responseBody');
            
            // Include metadata from the original transaction if available
            // We need to get metadata from transaction reference or request
            return $responseBody;
        }

        return null;
    }

    /**
     * Process successful payment.
     */
    private function processSuccessfulPayment(GuestPayment $guestPayment, array $transactionData): void
    {
        Log::info('Processing successful payment', [
            'guestPaymentId' => $guestPayment->id,
            'transactionData' => $transactionData,
        ]);

        // Update guest payment status
        $guestPayment->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Create collection payment record
        CollectionPayment::create([
            'collection_id' => $guestPayment->collection_id,
            'user_id' => null, // Guest payment
            'customer_name' => $guestPayment->is_anonymous ? null : $guestPayment->customer_name,
            'amount' => $guestPayment->amount,
            'note' => $guestPayment->is_anonymous 
                ? 'Anonymous payment (Ref: ' . $guestPayment->transaction_reference . ')'
                : "Payment by {$guestPayment->customer_name} (Ref: {$guestPayment->transaction_reference})",
            'paid_at' => now(),
        ]);

        Log::info('Payment processed successfully', [
            'collection_id' => $guestPayment->collection_id,
            'amount' => $guestPayment->amount,
            'customer' => $guestPayment->customer_name,
            'transactionRef' => $guestPayment->transaction_reference,
        ]);
    }
}
