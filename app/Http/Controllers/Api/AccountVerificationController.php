<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ZainPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccountVerificationController extends Controller
{
    public function __construct(private readonly ZainPayService $zainpay)
    {
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_name'      => 'required|string|max:255',
            'bank_code'      => 'nullable|string|max:20',
            'account_number' => 'required|string|size:10',
        ]);

        try {
            $bankCode = $validated['bank_code'] ?: $this->zainpay->resolveBankCode($validated['bank_name']);

            if (! $bankCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank not found. Please select from the list of available banks.',
                ]);
            }

            $accountName = $this->zainpay->validateDestinationAccount($bankCode, $validated['account_number']);

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

        } catch (\Throwable $e) {
            Log::error('Account verification error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Verification service unavailable. Please try again later.',
            ], 503);
        }
    }

    public function getBanks(): JsonResponse
    {
        $banks = $this->zainpay->getAvailableBanks();

        if (empty($banks)) {
            return response()->json(['success' => false, 'message' => 'Bank list unavailable. Please try again.'], 503);
        }

        return response()->json(['success' => true, 'banks' => $banks]);
    }
}
