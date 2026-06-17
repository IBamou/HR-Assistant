<?php

namespace App\Jobs;

use App\Enums\ProcessStatus;
use App\Models\Analysis;
use App\Models\Application;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyseCVJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $applicationId) {}

    public function handle(): void
    {
        $application = Application::find($this->applicationId);

        if (! $application || ! $application->user_id) {
            return;
        }

        Analysis::create([
            'application_id' => $application->id,
            'user_id' => $application->user_id,
            'status' => ProcessStatus::Pending,
        ]);
    }
}
