<?php

namespace App\Mail;

use App\Models\Referral;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReferralNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Referral $referral,
        public string $message
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Referral #{$this->referral->id} - {$this->referral->urgency}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.referral-notification',
            with: [
                'referral' => $this->referral,
                'message' => $this->message,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
