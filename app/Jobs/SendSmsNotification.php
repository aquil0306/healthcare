<?php

namespace App\Jobs;

use App\Models\Referral;
use App\Models\Staff;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendSmsNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Staff $staff,
        public Referral $referral,
        public string $message
    ) {
    }

    public function handle(): void
    {
        // In a real implementation, integrate with SMS service (Twilio, etc.)
        // For now, we'll just log it
        Log::info("SMS sent to staff {$this->staff->id}: {$this->message}");
        
        // Create notification record
        \App\Models\Notification::create([
            'staff_id' => $this->staff->id,
            'referral_id' => $this->referral->id,
            'message' => $this->message,
            'channel' => 'sms',
            'type' => 'referral',
            'sent_at' => now(),
        ]);
    }
}
