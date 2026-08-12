<?php

namespace App\Console\Commands;

use App\Models\Withdrawal;
use App\Services\ZainPayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncWithdrawalStatuses extends Command
{
    protected $signature   = 'withdrawals:sync-statuses';
    protected $description = 'Poll ZainPay for every processing/pending withdrawal and update its status.';

    public function __construct(private readonly ZainPayService $zainpay)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $withdrawals = Withdrawal::whereIn('status', ['processing', 'pending'])
            ->where('created_at', '<=', now()->subMinutes(2)) // skip brand-new ones
            ->orderBy('created_at')
            ->get();

        if ($withdrawals->isEmpty()) {
            $this->info('No processing withdrawals to sync.');
            return self::SUCCESS;
        }

        $this->info("Checking {$withdrawals->count()} withdrawal(s)…");

        $successStatuses = ['successful', 'success', 'completed', 'transferred', 'transfer', 'debit'];
        $failStatuses    = ['failed', 'cancelled', 'reversed', 'rejected', 'error'];

        foreach ($withdrawals as $withdrawal) {
            $txnRef = $withdrawal->transaction_reference;

            try {
                $result = $this->zainpay->verifyTransfer($txnRef);

                if (! $result) {
                    $this->line("  [{$withdrawal->id}] {$txnRef} — ZainPay returned no data, skipping.");
                    continue;
                }

                $txStatus = strtolower(
                    $result['status']          ??
                    $result['txnStatus']       ??
                    $result['transactionType'] ??
                    ''
                );

                if (in_array($txStatus, $successStatuses)) {
                    $withdrawal->update([
                        'status'         => 'completed',
                        'processed_at'   => now(),
                        'failure_reason' => null,
                    ]);
                    $this->info("  [{$withdrawal->id}] COMPLETED — ₦{$withdrawal->amount} for collection {$withdrawal->collection_id}");
                    Log::info('withdrawals:sync — marked completed', ['id' => $withdrawal->id, 'txnRef' => $txnRef]);
                    continue;
                }

                if (in_array($txStatus, $failStatuses)) {
                    $reason = $result['description'] ?? $result['failureReason'] ?? 'Transfer failed per ZainPay.';
                    $withdrawal->update([
                        'status'         => 'failed',
                        'failure_reason' => $reason,
                        'processed_at'   => now(),
                    ]);
                    $this->warn("  [{$withdrawal->id}] FAILED — {$reason}");
                    Log::warning('withdrawals:sync — marked failed', ['id' => $withdrawal->id, 'txnRef' => $txnRef, 'reason' => $reason]);
                    continue;
                }

                // ZainPay returned data but status is ambiguous — leave as-is
                $this->line("  [{$withdrawal->id}] still processing (ZainPay status: '{$txStatus}')");

            } catch (\Throwable $e) {
                $this->error("  [{$withdrawal->id}] Error: " . $e->getMessage());
                Log::error('withdrawals:sync error', ['id' => $withdrawal->id, 'error' => $e->getMessage()]);
            }
        }

        return self::SUCCESS;
    }
}
