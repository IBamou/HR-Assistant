<?php

use App\Jobs\AnalyseCVJob;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Volt\Volt;

test('authenticated user can access submit page', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $response = $this->get(route('offers.submit', $offer));

    $response->assertStatus(200);
});

test('unauthenticated user is redirected to login', function () {
    $offer = Offer::factory()->create();

    $response = $this->get(route('offers.submit', $offer));

    $response->assertRedirect('/login');
});

test('submit page shows offer title', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id, 'title' => 'Senior PHP Developer']);

    $this->actingAs($user);

    Volt::test('livewire.offers.submit', ['offer' => $offer])
        ->assertSee('Senior PHP Developer');
});

test('submit creates candidate, document, and application', function () {
    Queue::fake();

    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $extractedText = 'This is test CV text for John Doe.';

    $component = Volt::test('livewire.offers.submit', ['offer' => $offer]);

    $component->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('cvPath', 'pdfs/test.pdf')
        ->set('extractedText', $extractedText)
        ->call('submit');

    $this->assertDatabaseHas('candidates', [
        'email' => 'john@example.com',
        'user_id' => $user->id,
        'extracted_text' => $extractedText,
    ]);

    $this->assertDatabaseHas('documents', [
        'filename' => 'test.pdf',
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('applications', [
        'offer_id' => $offer->id,
        'user_id' => $user->id,
    ]);

    Queue::assertPushed(AnalyseCVJob::class);
});

test('submit with existing email links to existing candidate within same user scope', function () {
    Queue::fake();

    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);
    $existingCandidate = Candidate::factory()->create([
        'email' => 'jane@example.com',
        'name' => 'Jane Smith',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    Volt::test('livewire.offers.submit', ['offer' => $offer])
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('cvPath', 'pdfs/test.pdf')
        ->set('extractedText', 'This is test CV text.')
        ->call('submit');

    $this->assertDatabaseHas('candidates', [
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseCount('candidates', 1);
});

test('submit with existing email from another user creates separate candidate', function () {
    Queue::fake();

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);
    Candidate::factory()->create([
        'email' => 'jane@example.com',
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($user);

    Volt::test('livewire.offers.submit', ['offer' => $offer])
        ->set('name', 'Jane User')
        ->set('email', 'jane@example.com')
        ->set('cvPath', 'pdfs/test.pdf')
        ->set('extractedText', 'This is test CV text.')
        ->call('submit');

    $this->assertDatabaseCount('candidates', 2);

    $this->assertDatabaseHas('candidates', [
        'email' => 'jane@example.com',
        'user_id' => $user->id,
    ]);
});

test('blocks duplicate application for same offer and email', function () {
    Queue::fake();

    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);
    $candidate = Candidate::factory()->create([
        'email' => 'john@example.com',
        'user_id' => $user->id,
    ]);

    Application::factory()->create([
        'offer_id' => $offer->id,
        'candidate_id' => $candidate->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    Volt::test('livewire.offers.submit', ['offer' => $offer])
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('cvPath', 'pdfs/test.pdf')
        ->set('extractedText', 'This is test CV text.')
        ->call('submit');

    $this->assertDatabaseCount('applications', 1);
});

test('submit dispatches AnalyseCVJob', function () {
    Queue::fake();

    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Volt::test('livewire.offers.submit', ['offer' => $offer])
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('cvPath', 'pdfs/test.pdf')
        ->set('extractedText', 'This is test CV text.')
        ->call('submit');

    Queue::assertPushed(AnalyseCVJob::class);
});

test('validation rejects no name', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Volt::test('livewire.offers.submit', ['offer' => $offer])
        ->set('email', 'john@example.com')
        ->set('cvPath', 'pdfs/test.pdf')
        ->call('submit')
        ->assertHasErrors(['name']);
});

test('validation rejects no email', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Volt::test('livewire.offers.submit', ['offer' => $offer])
        ->set('name', 'John Doe')
        ->set('cvPath', 'pdfs/test.pdf')
        ->call('submit')
        ->assertHasErrors(['email']);
});

test('validation rejects invalid email', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Volt::test('livewire.offers.submit', ['offer' => $offer])
        ->set('name', 'John Doe')
        ->set('email', 'not-an-email')
        ->set('cvPath', 'pdfs/test.pdf')
        ->call('submit')
        ->assertHasErrors(['email']);
});

test('user cannot submit CV for another users offer', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user);

    $response = $this->get(route('offers.submit', $offer));

    $response->assertStatus(403);
});

test('submit redirects to offer show page with success flash', function () {
    Queue::fake();

    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Volt::test('livewire.offers.submit', ['offer' => $offer])
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('cvPath', 'pdfs/test.pdf')
        ->set('extractedText', 'This is test CV text.')
        ->call('submit')
        ->assertRedirect(route('offers.show', $offer));
});
