<?php

use App\Enums\ProcessStatus;
use App\Jobs\AnalyseCVJob;
use App\Models\Analysis;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Offer;
use App\Models\User;
use App\Services\AiClient;

use function Pest\Laravel\mock;

test('stores analysis on successful AI response', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id, 'required_skills' => ['PHP', 'Laravel']]);
    $candidate = Candidate::factory()->create([
        'user_id' => $user->id,
        'extracted_text' => 'John Doe has 5 years of PHP experience.',
    ]);
    $application = Application::factory()->create([
        'offer_id' => $offer->id,
        'candidate_id' => $candidate->id,
        'user_id' => $user->id,
    ]);

    $aiClient = mock(AiClient::class);
    $aiClient->shouldReceive('prompt')
        ->once()
        ->andReturn('{"matching_score":85,"extracted_skills":["PHP","Laravel","MySQL"],"missing_skills":["Docker","Redis"],"strengths":"Strong PHP background.","gaps":"No cloud experience.","justification":"Good match for the role.","recommendation":"shortlisted"}');

    app()->instance(AiClient::class, $aiClient);

    $job = new AnalyseCVJob($application->id);
    $job->handle();

    $analysis = Analysis::where('application_id', $application->id)->first();

    expect($analysis)->not->toBeNull()
        ->and($analysis->matching_score)->toBe(85)
        ->and($analysis->recommendation->value)->toBe('shortlisted')
        ->and($analysis->extracted_skills)->toBe(['PHP', 'Laravel', 'MySQL'])
        ->and($analysis->missing_skills)->toBe(['Docker', 'Redis'])
        ->and($analysis->strengths)->toBe('Strong PHP background.')
        ->and($analysis->gaps)->toBe('No cloud experience.')
        ->and($analysis->justification)->toBe('Good match for the role.')
        ->and($analysis->status)->toBe(ProcessStatus::Processed);
});

test('stores failed status when AI client throws exception', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);
    $candidate = Candidate::factory()->create([
        'user_id' => $user->id,
        'extracted_text' => 'Some CV text.',
    ]);
    $application = Application::factory()->create([
        'offer_id' => $offer->id,
        'candidate_id' => $candidate->id,
        'user_id' => $user->id,
    ]);

    $aiClient = mock(AiClient::class);
    $aiClient->shouldReceive('prompt')
        ->once()
        ->andThrow(new RuntimeException('AI service unavailable'));

    app()->instance(AiClient::class, $aiClient);

    $job = new AnalyseCVJob($application->id);
    $job->handle();

    $analysis = Analysis::where('application_id', $application->id)->first();

    expect($analysis)->not->toBeNull()
        ->and($analysis->status)->toBe(ProcessStatus::Failed);
});

test('silently exits when candidate has no extracted text', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);
    $candidate = Candidate::factory()->create([
        'user_id' => $user->id,
        'extracted_text' => null,
    ]);
    $application = Application::factory()->create([
        'offer_id' => $offer->id,
        'candidate_id' => $candidate->id,
        'user_id' => $user->id,
    ]);

    $job = new AnalyseCVJob($application->id);
    $job->handle();

    expect(Analysis::where('application_id', $application->id)->count())->toBe(0);
});

test('parses markdown-wrapped JSON from AI response', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id, 'required_skills' => ['PHP']]);
    $candidate = Candidate::factory()->create([
        'user_id' => $user->id,
        'extracted_text' => 'CV text.',
    ]);
    $application = Application::factory()->create([
        'offer_id' => $offer->id,
        'candidate_id' => $candidate->id,
        'user_id' => $user->id,
    ]);

    $aiClient = mock(AiClient::class);
    $aiClient->shouldReceive('prompt')
        ->once()
        ->andReturn('```json
{"matching_score":75,"recommendation":"shortlisted","extracted_skills":["PHP"],"missing_skills":[],"strengths":"Good.","gaps":"None.","justification":"Solid."}
```');

    app()->instance(AiClient::class, $aiClient);

    $job = new AnalyseCVJob($application->id);
    $job->handle();

    $analysis = Analysis::where('application_id', $application->id)->first();

    expect($analysis)->not->toBeNull()
        ->and($analysis->matching_score)->toBe(75)
        ->and($analysis->recommendation->value)->toBe('shortlisted')
        ->and($analysis->status)->toBe(ProcessStatus::Processed);
});

test('silently exits when application does not exist', function () {
    $aiClient = mock(AiClient::class);
    $aiClient->shouldNotReceive('prompt');

    app()->instance(AiClient::class, $aiClient);

    $job = new AnalyseCVJob(999);
    $job->handle();

    expect(Analysis::count())->toBe(0);
});
