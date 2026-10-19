<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnnouncementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $announcementSubject,
        public string $badge,
        public string $messageContent,
        public ?string $actionText = null,
        public ?string $actionUrl = null,
        public ?string $recipientName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->announcementSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.announcement',
            with: [
                'subject' => $this->announcementSubject,
                'badge' => $this->badge,
                'messageContent' => $this->messageContent,
                'actionText' => $this->actionText,
                'actionUrl' => $this->actionUrl,
                'recipientName' => $this->recipientName,
            ],
        );
    }
}
