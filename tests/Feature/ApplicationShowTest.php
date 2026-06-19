<?php

use App\Enums\ProcessStatus;
use App\Enums\Recommandation;
use App\Models\Analysis;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Offer;
use App\Models\User;
use App\Services\AiClient;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

it('displays the page for the application owner', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);
    $candidate = Candidate::factory()->create(['user_id' => $user->id, 'name' => 'Jane Dupont']);
    $application = Application::factory()->create([
        'user_id' => $user->id,
        'offer_id' => $offer->id,
        'candidate_id' => $candidate->id,
    ]);

    actingAs($user);

    $this->get(route('applications.show', $application))
        ->assertOk()
        ->assertSee('Jane Dupont')
        ->assertSee($offer->title);
});

it('returns 403 for other users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $owner->id]);

    actingAs($other);

    $this->get(route('applications.show', $application))->assertForbidden();
});

it('shows no analysis message when no analysis exists', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $user->id]);

    actingAs($user);

    Volt::test('livewire.applications.show', ['application' => $application])
        ->assertSee('No analysis has been run');
});

it('shows processed analysis data', function () {
    $user = User::factory()->create();
    $candidate = Candidate::factory()->create(['user_id' => $user->id, 'name' => 'John Test']);
    $application = Application::factory()->create([
        'user_id' => $user->id,
        'candidate_id' => $candidate->id,
    ]);
    $analysis = Analysis::factory()->create([
        'application_id' => $application->id,
        'user_id' => $user->id,
        'matching_score' => 78,
        'recommendation' => Recommandation::OnHold,
        'extracted_skills' => ['PHP', 'MySQL'],
        'missing_skills' => ['Redis'],
        'strengths' => 'Good PHP base.',
        'gaps' => 'No Redis.',
        'justification' => 'Matches basic requirements.',
        'raw_response' => '{"matching_score":78}',
        'status' => ProcessStatus::Processed,
    ]);

    actingAs($user);

    Volt::test('livewire.applications.show', ['application' => $application])
        ->assertSee('78%')
        ->assertSee('On Hold')
        ->assertSee('Good PHP base.')
        ->assertSee('No Redis.')
        ->assertSee('PHP')
        ->assertSee('MySQL')
        ->assertSee('Redis');
});

it('shows pending status', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $user->id]);
    Analysis::factory()->create([
        'application_id' => $application->id,
        'user_id' => $user->id,
        'status' => ProcessStatus::Pending,
    ]);

    actingAs($user);

    Volt::test('livewire.applications.show', ['application' => $application])
        ->assertSee('Pending');
});

it('shows failed status', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $user->id]);
    Analysis::factory()->create([
        'application_id' => $application->id,
        'user_id' => $user->id,
        'status' => ProcessStatus::Failed,
    ]);

    actingAs($user);

    Volt::test('livewire.applications.show', ['application' => $application])
        ->assertSee('Failed')
        ->assertSee('Re-analyse');
});

it('re-analyses deletes old analysis and creates new one inline', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id, 'required_skills' => ['PHP']]);
    $candidate = Candidate::factory()->create([
        'user_id' => $user->id,
        'extracted_text' => '5 years PHP.',
    ]);
    $application = Application::factory()->create([
        'user_id' => $user->id,
        'offer_id' => $offer->id,
        'candidate_id' => $candidate->id,
    ]);
    $oldAnalysis = Analysis::factory()->create([
        'application_id' => $application->id,
        'user_id' => $user->id,
        'status' => ProcessStatus::Processed,
    ]);

    $aiClient = mock(AiClient::class);
    $aiClient->shouldReceive('prompt')
        ->once()
        ->andReturn('{"matching_score":90,"recommendation":"shortlisted","extracted_skills":["PHP"],"missing_skills":[],"strengths":"Good.","gaps":"None.","justification":"Solid."}');
    app()->instance(AiClient::class, $aiClient);

    actingAs($user);

    Volt::test('livewire.applications.show', ['application' => $application])
        ->call('reAnalyse');

    $this->assertDatabaseMissing('analyses', ['id' => $oldAnalysis->id]);

    $newAnalysis = Analysis::where('application_id', $application->id)->first();
    expect($newAnalysis)->not->toBeNull()
        ->and($newAnalysis->matching_score)->toBe(90)
        ->and($newAnalysis->status)->toBe(ProcessStatus::Processed);
});

it('creates failed analysis when reAnalyse AI call throws', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id, 'required_skills' => ['PHP']]);
    $candidate = Candidate::factory()->create([
        'user_id' => $user->id,
        'extracted_text' => 'Some CV text.',
    ]);
    $application = Application::factory()->create([
        'user_id' => $user->id,
        'offer_id' => $offer->id,
        'candidate_id' => $candidate->id,
    ]);

    $aiClient = mock(AiClient::class);
    $aiClient->shouldReceive('prompt')
        ->once()
        ->andThrow(new RuntimeException('AI error'));
    app()->instance(AiClient::class, $aiClient);

    actingAs($user);

    Volt::test('livewire.applications.show', ['application' => $application])
        ->call('reAnalyse');

    $newAnalysis = Analysis::where('application_id', $application->id)->first();
    expect($newAnalysis)->not->toBeNull()
        ->and($newAnalysis->status)->toBe(ProcessStatus::Failed);
});

it('remove application deletes analysis and application then redirects', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);
    $application = Application::factory()->create([
        'user_id' => $user->id,
        'offer_id' => $offer->id,
    ]);
    $analysis = Analysis::factory()->create([
        'application_id' => $application->id,
        'user_id' => $user->id,
    ]);

    actingAs($user);

    Volt::test('livewire.applications.show', ['application' => $application])
        ->call('removeApplication')
        ->assertRedirect(route('offers.show', $offer));

    $this->assertDatabaseMissing('applications', ['id' => $application->id]);
    $this->assertDatabaseMissing('analyses', ['id' => $analysis->id]);
});
