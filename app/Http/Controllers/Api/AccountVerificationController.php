<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AccountVerificationController extends Controller
{
    /**
     * Verify bank account using Monnify API.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|size:10',
        ]);

        try {
            // Get bank code from bank name
            $bankCode = $this->getBankCode($validated['bank_name']);
            
            if (!$bankCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank not found. Please select from the list of available banks.',
                ]);
            }

            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification service unavailable. Please try again later.',
                ], 503);
            }

            // Call Monnify Validate Bank Account API
            $response = Http::timeout(30)->withToken($accessToken)->get(
                config('services.monnify.base_url') . '/api/v1/disbursements/account/validate',
                [
                    'accountNumber' => $validated['account_number'],
                    'bankCode' => $bankCode,
                ]
            );

            $result = $response->json();
            
            \Log::info('Monnify verification response:', [
                'status' => $response->status(),
                'response' => $result,
                'bank_code' => $bankCode,
                'account_number' => $validated['account_number'],
            ]);

            if (
                $response->successful()
                && ($result['requestSuccessful'] ?? false)
                && isset($result['responseBody']['accountName'])
            ) {
                return response()->json([
                    'success' => true,
                    'account_name' => $result['responseBody']['accountName'],
                    'account_number' => $validated['account_number'],
                    'bank_name' => $validated['bank_name'],
                ]);
            }
            
            // Log error details
            \Log::error('Monnify verification failed:', [
                'status_code' => $response->status(),
                'response_code' => $result['responseCode'] ?? 'N/A',
                'response_message' => $result['responseMessage'] ?? $result['error'] ?? 'N/A',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not verify account: ' . ($result['responseMessage'] ?? $result['error'] ?? 'Please check the details and try again.'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Account verification failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Verification service unavailable. Please try again later.',
            ], 503);
        }
    }

    /**
     * Get Monnify access token (cached).
     */
    private function getAccessToken(): ?string
    {
        return Cache::remember('monnify_access_token', 3500, function () {
            $response = Http::timeout(15)->withBasicAuth(
                config('services.monnify.api_key'),
                config('services.monnify.secret_key')
            )->post(config('services.monnify.base_url') . '/api/v1/auth/login');

            $result = $response->json();

            if ($response->successful() && isset($result['responseBody']['accessToken'])) {
                return $result['responseBody']['accessToken'];
            }

            \Log::error('Monnify auth failed:', [
                'status' => $response->status(),
                'response' => $result,
            ]);

            return null;
        });
    }

    /**
     * Get all banks from Monnify API.
     */
    public function getBanks(): JsonResponse
    {
        try {
            // Cache banks for 24 hours to reduce API calls
            $banks = Cache::remember('monnify_banks', 86400, function () {
                $response = Http::timeout(10)->withBasicAuth(
                    config('services.monnify.api_key'),
                    config('services.monnify.secret_key')
                )->get(config('services.monnify.base_url') . '/api/v1/banks');

                $result = $response->json();

                if ($response->successful() && isset($result['responseBody'])) {
                    return collect($result['responseBody'])
                        ->map(function ($bank) {
                            return [
                                'name' => $bank['name'],
                                'code' => $bank['code'],
                            ];
                        })
                        ->sortBy('name')
                        ->values()
                        ->toArray();
                }

                // Fallback to common Nigerian banks if API fails
                return $this->getFallbackBanks();
            });

            return response()->json([
                'success' => true,
                'banks' => $banks,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch banks: ' . $e->getMessage());
            
            // Return fallback banks from cache or default
            $banks = Cache::get('monnify_banks', $this->getFallbackBanks());
            
            return response()->json([
                'success' => true,
                'banks' => $banks,
            ]);
        }
    }

    /**
     * Get fallback list of common Nigerian banks.
     */
    private function getFallbackBanks(): array
    {
        return [
            ['name' => 'Access Bank', 'code' => '044'],
            ['name' => 'Fidelity Bank', 'code' => '070'],
            ['name' => 'First Bank', 'code' => '011'],
            ['name' => 'First City Monument Bank (FCMB)', 'code' => '214'],
            ['name' => 'Globus Bank', 'code' => '103'],
            ['name' => 'Guaranty Trust Bank (GTBank)', 'code' => '058'],
            ['name' => 'Heritage Bank', 'code' => '030'],
            ['name' => 'Keystone Bank', 'code' => '082'],
            ['name' => 'Kuda Bank', 'code' => '502'],
            ['name' => 'Moniepoint Bank', 'code' => '503'],
            ['name' => 'OPay', 'code' => '999992'],
            ['name' => 'PalmPay', 'code' => '999991'],
            ['name' => 'Parallex Bank', 'code' => '526'],
            ['name' => 'Polaris Bank', 'code' => '076'],
            ['name' => 'Providus Bank', 'code' => '101'],
            ['name' => 'Stanbic IBTC Bank', 'code' => '221'],
            ['name' => 'Standard Chartered Bank', 'code' => '068'],
            ['name' => 'Sterling Bank', 'code' => '232'],
            ['name' => 'Suntrust Bank', 'code' => '100'],
            ['name' => 'Titan Trust Bank', 'code' => '102'],
            ['name' => 'Union Bank', 'code' => '032'],
            ['name' => 'United Bank for Africa (UBA)', 'code' => '033'],
            ['name' => 'Unity Bank', 'code' => '215'],
            ['name' => 'Wema Bank', 'code' => '035'],
            ['name' => 'Zenith Bank', 'code' => '057'],
        ];
    }

    /**
     * Get bank code from bank name.
     */
    private function getBankCode(string $bankName): ?string
    {
        // Try to get from cached banks list
        $banks = Cache::get('monnify_banks');
        
        if (!$banks) {
            // Fallback: fetch banks synchronously (not recommended for production)
            try {
                $response = Http::withBasicAuth(
                    config('services.monnify.api_key'),
                    config('services.monnify.secret_key')
                )->get(config('services.monnify.base_url') . '/api/v1/banks');

                $result = $response->json();
                
                if ($response->successful() && isset($result['responseBody'])) {
                    $banks = collect($result['responseBody'])
                        ->map(function ($bank) {
                            return [
                                'name' => $bank['name'],
                                'code' => $bank['code'],
                            ];
                        })
                        ->toArray();
                } else {
                    return null;
                }
            } catch (\Exception $e) {
                return null;
            }
        }

        // Find matching bank (case-insensitive partial match)
        foreach ($banks as $bank) {
            if (stripos($bank['name'], $bankName) !== false || stripos($bankName, $bank['name']) !== false) {
                return $bank['code'];
            }
        }

        return null;
    }
}
