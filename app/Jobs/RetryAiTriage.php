<?php

namespace App\Jobs;

use App\Models\Referral;
use App\Models\AiTriageLog;
use App\Services\AiTriageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RetryAiTriage implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Referral $referral,
        public AiTriageLog $log,
        public int $attempt
    ) {
    }

    public function handle(AiTriageService $service): void
    {
        $service->retryTriage($this->referral, $this->log, $this->attempt);
    }
}
