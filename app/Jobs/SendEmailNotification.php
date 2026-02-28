<?php

namespace App\Jobs;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendEmailNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public Referral $referral,
        public string $message
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)->send(
            new \App\Mail\ReferralNotification($this->referral, $this->message)
        );
    }
}
