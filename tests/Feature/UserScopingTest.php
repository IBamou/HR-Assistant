<?php

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Document;
use App\Models\Offer;
use App\Models\User;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

test('user cannot see another users candidate', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $candidate = Candidate::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user);

    $visible = Candidate::ownedByCurrentUser()->get();

    expect($visible)->toHaveCount(0);
});

test('user cannot see another users document', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Document::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user);

    $visible = Document::ownedByCurrentUser()->get();

    expect($visible)->toHaveCount(0);
});

test('user cannot see another users application', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Application::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user);

    $visible = Application::ownedByCurrentUser()->get();

    expect($visible)->toHaveCount(0);
});

test('show page only displays applications from current user scope', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);

    $ownCandidate = Candidate::factory()->create(['user_id' => $user->id]);
    $otherCandidate = Candidate::factory()->create(['user_id' => $otherUser->id]);

    Application::factory()->create([
        'offer_id' => $offer->id,
        'candidate_id' => $ownCandidate->id,
        'user_id' => $user->id,
    ]);

    Application::factory()->create([
        'offer_id' => $offer->id,
        'candidate_id' => $otherCandidate->id,
        'user_id' => $otherUser->id,
    ]);

    actingAs($user);

    Volt::test('livewire.offers.show', ['offer' => $offer])
        ->assertSee($ownCandidate->name)
        ->assertDontSee($otherCandidate->name);
});
