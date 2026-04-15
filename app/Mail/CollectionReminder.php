<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CollectionReminder extends Mailable
{
    use Queueable, SerializesModels;

    public string $collectionName;
    public string $organizerName;
    public string $deadline;
    public float $amount;
    public string $slug;
    public ?string $customMessage;

    public function __construct(
        string $collectionName,
        string $organizerName,
        string $deadline,
        float $amount,
        string $slug,
        ?string $customMessage = null
    ) {
        $this->collectionName = $collectionName;
        $this->organizerName = $organizerName;
        $this->deadline = $deadline;
        $this->amount = $amount;
        $this->slug = $slug;
        $this->customMessage = $customMessage;
    }

    public function build(): self
    {
        return $this->subject("Reminder: {$this->collectionName} - Payment Due")
            ->view('emails.collection-reminder')
            ->with([
                'collectionName' => $this->collectionName,
                'organizerName' => $this->organizerName,
                'deadline' => $this->deadline,
                'amount' => $this->amount,
                'slug' => $this->slug,
                'customMessage' => $this->customMessage,
            ]);
    }
}
