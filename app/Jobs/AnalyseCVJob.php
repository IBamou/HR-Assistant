<?php

namespace App\Jobs;

use App\Ai\Agents\CvScorer;
use App\Enums\ProcessStatus;
use App\Enums\Recommandation;
use App\Models\Analysis;
use App\Models\Application;
use App\Services\AiClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyseCVJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $applicationId) {}

    public function handle(): void
    {
        $application = Application::with('candidate', 'offer')->find($this->applicationId);

        if (! $application || ! $application->candidate || ! $application->candidate->extracted_text) {
            return;
        }

        $analysis = Analysis::create([
            'application_id' => $application->id,
            'user_id' => $application->user_id,
            'status' => ProcessStatus::Pending,
        ]);

        try {
            $cvText = mb_substr($application->candidate->extracted_text, 0, 30000);
            $offer = $application->offer;

            $agent = new CvScorer(
                offerTitle: $offer->title,
                offerDescription: $offer->description,
                requiredSkills: implode(', ', $offer->required_skills ?? []),
            );

            $client = app(AiClient::class);
            $response = $client->prompt($agent, $cvText);

            $data = AiClient::parseResponse($response);

            if (! $data) {
                throw new \RuntimeException('Failed to parse AI response as JSON');
            }

            $analysis->update([
                'matching_score' => $data['matching_score'] ?? null,
                'recommendation' => isset($data['recommendation']) ? Recommandation::from($data['recommendation']) : null,
                'extracted_skills' => $data['extracted_skills'] ?? [],
                'missing_skills' => $data['missing_skills'] ?? [],
                'strengths' => $data['strengths'] ?? null,
                'gaps' => $data['gaps'] ?? null,
                'justification' => $data['justification'] ?? null,
                'raw_response' => $response,
                'status' => ProcessStatus::Processed,
            ]);
        } catch (\Throwable $e) {
            $analysis->update([
                'status' => ProcessStatus::Failed,
            ]);
        }
    }
}
