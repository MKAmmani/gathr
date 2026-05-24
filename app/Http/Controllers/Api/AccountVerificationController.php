<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FlutterwaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccountVerificationController extends Controller
{
    public function __construct(private readonly FlutterwaveService $flutterwave)
    {
    }

    /**
     * Verify bank account using Flutterwave API.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_name'      => 'required|string|max:255',
            'account_number' => 'required|string|size:10',
        ]);

        try {
            $bankCode = $this->flutterwave->resolveBankCode($validated['bank_name']);

            if (! $bankCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank not found. Please select from the list of available banks.',
                ]);
            }

            $accountName = $this->flutterwave->validateDestinationAccount($bankCode, $validated['account_number']);

            return response()->json([
                'success'        => true,
                'account_name'   => $accountName,
                'account_number' => $validated['account_number'],
                'bank_name'      => $validated['bank_name'],
            ]);

        } catch (\RuntimeException $e) {
            Log::error('Account verification failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Could not verify account: ' . $e->getMessage(),
            ]);

        } catch (\Exception $e) {
            Log::error('Account verification error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Verification service unavailable. Please try again later.',
            ], 503);
        }
    }

    /**
     * Return list of Nigerian banks from Flutterwave.
     */
    public function getBanks(): JsonResponse
    {
        try {
            $banks = $this->flutterwave->getBanks();

            if (empty($banks)) {
                $banks = $this->getFallbackBanks();
            }

            return response()->json([
                'success' => true,
                'banks'   => $banks,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch banks: ' . $e->getMessage());

            return response()->json([
                'success' => true,
                'banks'   => $this->getFallbackBanks(),
            ]);
        }
    }

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
}
