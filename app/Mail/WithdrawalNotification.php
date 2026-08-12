<?php

namespace App\Mail;

use App\Models\Collection;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WithdrawalNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $contributorName,
        public readonly string $collectionName,
        public readonly string $organizerName,
        public readonly float  $withdrawalAmount,
        public readonly float  $totalRaised,
        public readonly string $collectionSlug,
    ) {}

    public function build(): self
    {
        return $this->subject("Withdrawal Update: {$this->collectionName}")
            ->view('emails.withdrawal-notification');
    }
}
