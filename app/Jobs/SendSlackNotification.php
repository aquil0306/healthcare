<?php

namespace App\Jobs;

use App\Models\Referral;
use App\Models\Staff;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendSlackNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Staff $staff,
        public Referral $referral,
        public string $message
    ) {}

    public function handle(): void
    {
        // In a real implementation, integrate with Slack API
        // For now, we'll just log it
        Log::info("Slack notification sent to staff {$this->staff->id}: {$this->message}");

        // Create notification record
        \App\Models\Notification::create([
            'staff_id' => $this->staff->id,
            'referral_id' => $this->referral->id,
            'message' => $this->message,
            'channel' => 'slack',
            'type' => 'referral',
            'sent_at' => now(),
        ]);
    }
}
