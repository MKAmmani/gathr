<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwaveService
{
    private string $baseUrl = 'https://api.flutterwave.com/v3';

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(30)
            ->withToken(config('services.flutterwave.secret_key'))
            ->acceptJson();
    }

    public function getBanks(): array
    {
        return Cache::remember('flutterwave_banks', 86400, function () {
            $response = $this->http()->get("{$this->baseUrl}/banks/NG");
            $result   = $response->json();

            if ($response->successful() && isset($result['data'])) {
                return collect($result['data'])
                    ->map(fn ($bank) => [
                        'name' => $bank['name'],
                        'code' => $bank['code'],
                    ])
                    ->sortBy('name')
                    ->values()
                    ->toArray();
            }

            Log::warning('Flutterwave getBanks failed', ['response' => $result]);

            return [];
        });
    }

    public function resolveBankCode(string $bankName): ?string
    {
        $normalized = trim(strtolower($bankName));

        foreach ($this->getBanks() as $bank) {
            $name = trim(strtolower($bank['name'] ?? ''));

            if ($name === $normalized || str_contains($name, $normalized) || str_contains($normalized, $name)) {
                return $bank['code'] ?? null;
            }
        }

        return null;
    }

    public function validateDestinationAccount(string $bankCode, string $accountNumber): string
    {
        $response = $this->http()->post("{$this->baseUrl}/accounts/resolve", [
            'account_number' => $accountNumber,
            'account_bank'   => $bankCode,
        ]);

        $result = $response->json();

        if (
            $response->successful()
            && ($result['status'] ?? '') === 'success'
            && isset($result['data']['account_name'])
        ) {
            return $result['data']['account_name'];
        }

        throw new \RuntimeException($result['message'] ?? 'Unable to validate destination account.');
    }

    public function initiateSingleTransfer(array $payload): array
    {
        $response = $this->http()->post("{$this->baseUrl}/transfers", $payload);
        $result   = $response->json();

        if (! $response->successful() || ($result['status'] ?? '') !== 'success') {
            throw new \RuntimeException($result['message'] ?? 'Flutterwave transfer request failed.');
        }

        return $result['data'] ?? [];
    }

    public function verifyTransaction(string $transactionId): ?array
    {
        $response = $this->http()->get("{$this->baseUrl}/transactions/{$transactionId}/verify");
        $result   = $response->json();

        if ($response->successful() && ($result['status'] ?? '') === 'success') {
            return $result['data'] ?? null;
        }

        return null;
    }

    public function verifyWebhookSignature(?string $signature): bool
    {
        if (! $signature) {
            return false;
        }

        return hash_equals(config('services.flutterwave.webhook_secret'), $signature);
    }
}
