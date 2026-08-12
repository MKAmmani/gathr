<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZainPayService
{
    private string $baseUrl = 'https://api.zainpay.ng/';

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(25)
            ->connectTimeout(8)
            ->withToken(config('services.zainpay.private_key'))
            ->acceptJson()
            ->withHeaders(['Expect' => ''])
            ->withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                    CURLOPT_SSL_VERIFYPEER => true,
                ],
            ]);
    }

    private function httpPrivate(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(25)
            ->connectTimeout(8)
            ->withToken(config('services.zainpay.private_key'))
            ->acceptJson()
            ->withHeaders(['Expect' => ''])
            ->withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                    CURLOPT_SSL_VERIFYPEER => true,
                ],
            ]);
    }

    // ─── Banks ───────────────────────────────────────────────────────────────

    public function getBanks(): array
    {
        $cached = Cache::get('zainpay_banks');
        if (is_array($cached) && ! empty($cached)) {
            return $cached;
        }

        $response = $this->http()->get("{$this->baseUrl}bank/list", [
            'zainboxCode' => config('services.zainpay.zainbox_code'),
        ]);
        $result = $response->json();

        Log::info('ZainPay bank list', ['status' => $response->status()]);

        if ($response->successful() && ! empty($result['data'])) {
            $banks = collect($result['data'])
                ->map(fn ($b) => [
                    'name' => $b['name'] ?? $b['bankName'] ?? '',
                    'code' => $b['code'] ?? $b['bankCode'] ?? '',
                ])
                ->filter(fn ($b) => ! empty($b['name']) && ! empty($b['code']))
                ->sortBy('name')
                ->values()
                ->toArray();

            if (! empty($banks)) {
                Cache::put('zainpay_banks', $banks, 86400);
                return $banks;
            }
        }

        Cache::forget('zainpay_banks');
        Log::warning('ZainPay getBanks failed', ['status' => $response->status(), 'body' => $result]);
        return [];
    }

    public function getAvailableBanks(): array
    {
        return $this->getBanks();
    }

    public function resolveBankCode(string $bankName): ?string
    {
        $normalized = trim(strtolower($bankName));
        $banks      = $this->getBanks();

        // Pass 1: exact name or code match
        foreach ($banks as $bank) {
            $name = trim(strtolower($bank['name'] ?? ''));
            $code = trim(strtolower((string) ($bank['code'] ?? '')));
            if ($name === $normalized || $code === $normalized) {
                return $bank['code'] ?? null;
            }
        }

        // Pass 2: bank list name starts with the user's input (e.g. "GTBank" starts with "GT")
        foreach ($banks as $bank) {
            $name = trim(strtolower($bank['name'] ?? ''));
            if ($normalized && str_starts_with($name, $normalized)) {
                return $bank['code'] ?? null;
            }
        }

        // Pass 3: user's input starts with the bank list name (handles abbreviated input)
        foreach ($banks as $bank) {
            $name = trim(strtolower($bank['name'] ?? ''));
            if ($name && str_starts_with($normalized, $name)) {
                return $bank['code'] ?? null;
            }
        }

        return null;
    }

    // ─── Account Validation ──────────────────────────────────────────────────

    public function validateDestinationAccount(string $bankCode, string $accountNumber): string
    {
        $response = $this->httpPrivate()->get("{$this->baseUrl}bank/name-enquiry", [
            'bankCode'      => $bankCode,
            'accountNumber' => $accountNumber,
        ]);

        $result = $response->json();

        Log::info('ZainPay name enquiry', [
            'status'        => $response->status(),
            'bankCode'      => $bankCode,
            'accountNumber' => $accountNumber,
            'response'      => $result,
        ]);

        if ($response->status() === 401) {
            throw new \RuntimeException('ZainPay rejected the API key. Check ZAINPAY_PRIVATE_KEY.');
        }

        $data = $result['data'] ?? [];
        if (($result['code'] ?? '') === '00' && ! empty($data)) {
            $name = $data['accountName'] ?? $data['name'] ?? '';
            if ($name) return $name;
        }

        $code        = $result['code'] ?? '';
        $description = strtolower($result['description'] ?? '');

        if ($code === '20' || str_contains($description, 'invalid account')) {
            throw new \RuntimeException(
                'Sorry, we could not verify your account. Please recheck your details and try again.'
            );
        }

        throw new \RuntimeException($result['description'] ?? $result['message'] ?? 'Unable to validate destination account.');
    }

    // ─── Card Payment ─────────────────────────────────────────────────────────

    /**
     * Initialize a card payment. Returns a redirect URL. Amount in Naira.
     */
    public function initiateCardPayment(
        int $amountNaira,
        string $txnRef,
        string $email,
        string $phone,
        string $callbackUrl
    ): string {
        $response = $this->http()->post("{$this->baseUrl}zainbox/card/initialize/payment", [
            'amount'       => (string) $amountNaira,
            'txnRef'       => $txnRef,
            'emailAddress' => $email ?: 'noreply@gathr.ng',
            'mobileNumber' => preg_replace('/\D/', '', $phone ?: '08000000000') ?: '08000000000',
            'zainboxCode'  => config('services.zainpay.zainbox_code'),
            'callBackUrl'  => $callbackUrl,
        ]);

        $result = $response->json();

        Log::info('ZainPay card init', ['status' => $response->status(), 'txnRef' => $txnRef, 'response' => $result]);

        // Per ZainPay docs, `data` is the redirect URL string itself; keep the
        // array fallbacks in case the shape ever changes.
        $data = $result['data'] ?? '';
        $url  = is_string($data)
            ? $data
            : ($data['paymentUrl'] ?? $data['link'] ?? $data['checkoutUrl'] ?? $data['url'] ?? '');

        if (($result['code'] ?? '') === '00' && $url) {
            return $url;
        }

        throw new \RuntimeException($result['description'] ?? $result['message'] ?? 'ZainPay card payment initialization failed.');
    }

    // ─── Dynamic Virtual Account (DVA) ───────────────────────────────────────

    /**
     * Create a per-transaction temporary virtual account (NUBAN).
     * Amount MUST be in KOBO. Duration in seconds (300–259200).
     * Returns the full DVA data including accountNumber, totalAmount (what customer must transfer).
     */
    public function createDynamicVirtualAccount(
        int $amountKobo,
        string $txnRef,
        string $email,
        string $callbackUrl,
        int $durationSeconds = 2880,
        string $bankType = 'gtBank'
    ): array {
        // ZainPay enforces 300 seconds (5 min) to 72 hours for DVA duration
        $durationSeconds = max(300, min(259200, $durationSeconds));

        $response = $this->http()->post("{$this->baseUrl}virtual-account/dynamic/create/request", [
            'bankType'    => $bankType,
            'email'       => $email ?: 'noreply@gathr.ng',
            'amount'      => (string) $amountKobo,
            'zainboxCode' => config('services.zainpay.zainbox_code'),
            'txnRef'      => $txnRef,
            'duration'    => $durationSeconds,
            'accountName' => 'Zainpay Checkout', // fixed value required by ZainPay
            'callBackUrl' => $callbackUrl,
        ]);

        $result = $response->json();

        Log::info('ZainPay DVA create', ['status' => $response->status(), 'txnRef' => $txnRef, 'response' => $result]);

        if ($response->status() === 401) {
            throw new \RuntimeException('ZainPay rejected the API key. Check ZAINPAY_SECRET_KEY.');
        }

        if (($result['code'] ?? '') === '00' && ! empty($result['data'])) {
            return $result['data'];
        }

        throw new \RuntimeException($result['description'] ?? $result['message'] ?? 'Failed to create payment account.');
    }

    /**
     * Poll the status of a DVA payment. Returns null if not found.
     */
    public function getDvaStatus(string $txnRef): ?array
    {
        $response = $this->http()->get("{$this->baseUrl}virtual-account/dynamic/deposit/status/{$txnRef}");
        $result   = $response->json();

        Log::info('ZainPay DVA status', ['status' => $response->status(), 'txnRef' => $txnRef, 'code' => $result['code'] ?? null]);

        $code = $result['code'] ?? '';
        if (($code === '00' || $code === '200 OK') && isset($result['data'])) {
            return $result['data'];
        }

        return null;
    }

    // ─── Deposit Verification ─────────────────────────────────────────────────

    /**
     * Verify a deposit (bank transfer / DVA payment) by txnRef received in webhook.
     * Endpoint: /virtual-account/wallet/deposit/verify/v2/{txnRef}
     */
    public function verifyDeposit(string $txnRef): ?array
    {
        // Primary: zainbox/transactions (works for DVA deposits)
        $response = $this->httpPrivate()->get("{$this->baseUrl}zainbox/transactions", ['txnRef' => $txnRef]);
        $result   = $response->json();

        Log::info('ZainPay deposit verify', ['status' => $response->status(), 'txnRef' => $txnRef, 'code' => $result['code'] ?? null]);

        if (($result['code'] ?? '') === '00' && ! empty($result['data'])) {
            $tx = $result['data'][0];

            // Guard: ensure this transaction actually belongs to the txnRef we asked for.
            // zainbox/transactions can return all zainbox transactions when the filter is loose.
            $responseTxnRef = $tx['transactionRef'] ?? $tx['txnRef'] ?? null;
            if ($responseTxnRef && $responseTxnRef !== $txnRef) {
                Log::warning('ZainPay verifyDeposit: txnRef mismatch — discarding', [
                    'requested' => $txnRef,
                    'received'  => $responseTxnRef,
                ]);
                return null;
            }

            return [
                'txnRef'             => $txnRef,
                'txnStatus'          => $tx['transactionType'] === 'deposit' ? 'successful' : 'failed',
                'amount'             => (int) $tx['amount'],
                'amountAfterCharges' => (int) $tx['amount'],
                'narration'          => $tx['narration'] ?? '',
                'transactionDate'    => $tx['transactionDate'] ?? null,
                'raw'                => $tx,
            ];
        }

        return null;
    }

    /**
     * Verify an outbound transfer (withdrawal) by its txnRef.
     * Endpoint: /virtual-account/wallet/transaction/verify/{txnRef}
     */
    public function verifyTransfer(string $txnRef): ?array
    {
        // The dedicated wallet/transaction/verify endpoint returns HTTP 405 for our plan.
        // Use zainbox/transactions (the same endpoint that works for deposit verification),
        // which lists every zainbox transaction — including outbound transfers — and filter
        // by txnRef. Authenticated with the private key, like the other verified reads.
        $response = $this->httpPrivate()->get("{$this->baseUrl}zainbox/transactions", ['txnRef' => $txnRef]);
        $result   = $response->json();

        Log::info('ZainPay transfer verify', ['status' => $response->status(), 'txnRef' => $txnRef, 'code' => $result['code'] ?? null]);

        if (($result['code'] ?? '') !== '00' || empty($result['data'])) {
            return null;
        }

        // Find the row that actually matches our txnRef — zainbox/transactions can return
        // unrelated rows when the filter is loose.
        $tx = collect($result['data'])->first(function ($row) use ($txnRef) {
            $ref = $row['transactionRef'] ?? $row['txnRef'] ?? null;
            return $ref === $txnRef;
        });

        if (! $tx) {
            return null;
        }

        // Normalize to the shape callers expect: a "status" they can match against their
        // success/fail lists. ZainPay marks outbound transfers with transactionType "transfer".
        $type = strtolower($tx['transactionType'] ?? $tx['txnType'] ?? '');

        return array_merge($tx, [
            'status' => $type ?: ($tx['status'] ?? ''),
        ]);
    }

    // ─── Withdrawal Transfer ──────────────────────────────────────────────────

    /**
     * Initiate a payout to the organizer's bank account.
     * Amount MUST be in KOBO. Success code from ZainPay is "200 OK" (not "00").
     */
    public function initiateTransfer(
        string $destAccountNumber,
        string $destBankCode,
        int $amountKobo,
        string $sourceAccountNumber,
        string $sourceBankCode,
        string $txnRef,
        string $narration
    ): array {
        // Transfers can be slow under ZainPay load — use a longer timeout than the default.
        // ConnectionException (timeout) is handled in WithdrawController to keep status as 'processing'.

        // ZainPay's bank/transfer/v2 rejects any narration containing non-ASCII bytes (emoji,
        // accented letters, control chars) with a generic "Funds Transfer Failed" (code 04) —
        // at every amount. Collection names routinely carry emoji (e.g. "Field trip to Sudan 🇸🇩"),
        // which flow into the narration and silently break every payout for that collection.
        // Strip the narration to printable ASCII before sending. Verified against the live API:
        // identical ₦100 transfer fails with an emoji narration, succeeds once sanitized.
        $narration = $this->sanitizeNarration($narration);

        $payload = [
            'destinationAccountNumber' => $destAccountNumber,
            'destinationBankCode'      => $destBankCode,
            'amount'                   => (string) $amountKobo,
            'sourceAccountNumber'      => $sourceAccountNumber,
            'sourceBankCode'           => $sourceBankCode,
            'zainboxCode'              => config('services.zainpay.zainbox_code'),
            'txnRef'                   => $txnRef,
            'narration'                => $narration,
            'callbackUrl'              => config('app.url') . '/webhooks/zainpay',
        ];

        Log::info('ZainPay transfer request', ['txnRef' => $txnRef, 'payload' => $payload]);

        $response = $this->http()->timeout(60)->post("{$this->baseUrl}bank/transfer/v2", $payload);

        $result = $response->json();

        Log::info('ZainPay transfer', ['status' => $response->status(), 'txnRef' => $txnRef, 'response' => $result]);

        if ($response->status() === 401) {
            throw new \RuntimeException('ZainPay rejected the API key. Check ZAINPAY_SECRET_KEY.');
        }

        // Success signals seen from ZainPay's bank/transfer/v2 (all treated as accepted):
        //   code "00"       — synchronous success (verified live)
        //   code "01"       — "successful queued" async transfer (ZainPay PHP SDK fixture)
        //   code "200 OK"   — legacy/documented form
        //   data.status "success" — verified live alongside code "00"
        // The top-level `status` field is always "200 OK" (it mirrors the HTTP status) even on
        // failure, so it is NOT a reliable success indicator — only `code`/`data.status` are.
        $code       = trim((string) ($result['code'] ?? ''));
        $dataStatus = strtolower((string) ($result['data']['status'] ?? ''));

        $successCodes = ['00', '01', '200 OK'];

        if (in_array($code, $successCodes, true) || $dataStatus === 'success') {
            return $result['data'] ?? [];
        }

        $description = strtolower($result['description'] ?? $result['message'] ?? '');
        $failureNote = $result['data']['failureReason'] ?? null;

        if ($failureNote && stripos($failureNote, 'insufficient') !== false) {
            $reason = 'Insufficient balance in the ZainPay payout wallet. Please ensure your ZainPay account is funded.';
        } elseif (str_contains($description, 'insufficient') || str_contains($description, 'balance')) {
            $reason = 'Insufficient balance in the ZainPay payout wallet. Please ensure your ZainPay account is funded.';
        } elseif (str_contains($description, 'no rows') || str_contains($description, 'not found') || $code === '25') {
            $reason = 'Payout source account not found in ZainPay. Please verify your source virtual account number and bank code in your environment settings.';
        } elseif ($code === '04') {
            $reason = 'Payout was declined by ZainPay. This is typically due to insufficient wallet balance or an invalid source account. Please check your ZainPay dashboard.';
        } elseif ($code === '09') {
            $reason = 'A transfer is already in progress on this account. Please wait a few minutes and try again.';
        } elseif ($code === '57') {
            $reason = 'Transfer not permitted on this account. Please contact ZainPay support.';
        } elseif ($code === '12') {
            $reason = 'Invalid transfer request. Please check the destination account details and try again.';
        } else {
            $reason = $failureNote ?? $result['description'] ?? $result['message'] ?? 'ZainPay transfer failed. Please try again or contact support.';
        }

        throw new \RuntimeException($reason);
    }

    /**
     * Reduce a transfer narration to characters ZainPay's bank/transfer/v2 accepts.
     * Non-ASCII bytes (emoji, accents) make the endpoint return code 04 "Funds Transfer
     * Failed" at any amount, so we keep only printable ASCII, collapse whitespace, cap the
     * length, and never return an empty string.
     */
    private function sanitizeNarration(string $narration): string
    {
        // Drop everything outside the printable-ASCII range (0x20–0x7E): emoji, accented
        // characters, control bytes, etc.
        $ascii = preg_replace('/[^\x20-\x7E]/', '', $narration) ?? '';
        // Collapse whitespace runs (including any left by stripped emoji) and trim.
        $ascii = trim(preg_replace('/\s+/', ' ', $ascii) ?? '');
        // Keep it well under ZainPay's narration length limit.
        if (strlen($ascii) > 100) {
            $ascii = rtrim(substr($ascii, 0, 100));
        }

        return $ascii !== '' ? $ascii : 'Gathr payout';
    }

    // ─── Webhook ──────────────────────────────────────────────────────────────

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = config('services.zainpay.webhook_secret');

        if (! $secret) {
            return true; // no secret configured — allow all
        }

        $signature = $request->header('x-webhook-signature')
            ?? $request->header('verif-hash')
            ?? $request->header('x-zainpay-signature')
            ?? $request->header('authorization')
            ?? '';

        if (! $signature) {
            // ZainPay may not send a signature header on all plans.
            // Log for visibility but allow the webhook through so payments aren't silently dropped.
            Log::warning('ZainPay webhook: no signature header — processing anyway.', [
                'ip'      => $request->ip(),
                'headers' => array_keys($request->headers->all()),
            ]);
            return true;
        }

        // Try HMAC-SHA512 (standard)
        $computed = hash_hmac('sha512', $request->getContent(), $secret);
        if (hash_equals($computed, strtolower($signature))) return true;

        // Try plain comparison (some ZainPay plans send the raw key)
        if (hash_equals($secret, $signature)) return true;

        Log::warning('ZainPay webhook: signature mismatch — rejecting.', ['ip' => $request->ip()]);
        return false;
    }

    // ─── Withdrawal Source Account ────────────────────────────────────────────

    public function findSourceAccount(int $amountKobo): ?array
    {
        $vaNumber   = config('services.zainpay.source_va_number');
        $vaBankCode = config('services.zainpay.source_va_bank_code');

        if ($vaNumber && $vaBankCode) {
            return ['accountNumber' => $vaNumber, 'bankCode' => $vaBankCode];
        }

        $accounts    = $this->getVirtualAccounts();
        $amountNaira = $amountKobo / 100;

        foreach ($accounts as $account) {
            $balance       = (float) ($account['balance'] ?? $account['availableBalance'] ?? $account['currentBalance'] ?? -1);
            $bankCode      = $account['bankCode'] ?? $this->resolveBankCodeForVaType($account['bankType'] ?? $account['bankName'] ?? '');
            $accountNumber = $account['accountNumber'] ?? $account['virtualAccountNumber'] ?? '';

            if ($accountNumber && $bankCode && ($balance < 0 || $balance >= $amountNaira)) {
                return ['accountNumber' => $accountNumber, 'bankCode' => $bankCode];
            }
        }

        if (! empty($accounts)) {
            $first    = $accounts[0];
            $bankCode = $first['bankCode'] ?? $this->resolveBankCodeForVaType($first['bankType'] ?? $first['bankName'] ?? '');
            return [
                'accountNumber' => $first['accountNumber'] ?? $first['virtualAccountNumber'] ?? '',
                'bankCode'      => $bankCode ?: '000007',
            ];
        }

        return null;
    }

    private function getVirtualAccounts(): array
    {
        $zainboxCode = config('services.zainpay.zainbox_code');
        $response    = $this->http()->get("{$this->baseUrl}zainbox/virtual-accounts/{$zainboxCode}");
        $result      = $response->json();
        $data        = $result['data'] ?? [];
        return is_array($data) ? $data : [];
    }

    // NIP codes as returned by ZainPay's own /bank/list endpoint.
    // Fidelity is ZainPay's partner bank since the July 2026 migration off Zain MFB.
    private function resolveBankCodeForVaType(string $type): string
    {
        return match (strtolower(trim($type))) {
            'wemabank', 'wema', 'wema bank'             => '000017',
            'polaris', 'polaris bank'                   => '000008',
            'fidelitybank', 'fidelity', 'fidelity bank' => '000007',
            'fcmb', 'first city monument bank'          => '000003',
            'gtbank', 'gtb', 'guaranty trust bank'      => '000013',
            default                                     => '000007',
        };
    }
}
