<?php

use App\Models\Offer;
use App\Models\User;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

it('redirects to login for unauthenticated users', function () {
    $this->get(route('offers.index'))->assertRedirect(route('login'));
});

it('displays offers index page', function () {
    $user = User::factory()->create();
    actingAs($user)->get(route('offers.index'))->assertOk();
});

it('displays create offer form', function () {
    $user = User::factory()->create();
    actingAs($user)->get(route('offers.create'))->assertOk();
});

it('creates a new offer with valid data', function () {
    $user = User::factory()->create();

    $offer = Offer::factory()->make([
        'user_id' => $user->id,
        'required_skills' => ['PHP', 'Laravel'],
    ]);

    actingAs($user);

    Volt::test('livewire.offers.create')
        ->set('title', $offer->title)
        ->set('description', $offer->description)
        ->set('responsibilities', $offer->responsibilities)
        ->set('required_skills', $offer->required_skills)
        ->set('soft_skills', $offer->soft_skills ?? [])
        ->set('min_experience_level', $offer->min_experience_level?->value)
        ->set('education_level', $offer->education_level)
        ->set('employment_type', $offer->employment_type?->value)
        ->set('location', $offer->location)
        ->call('store')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('offers', [
        'user_id' => $user->id,
        'title' => $offer->title,
    ]);
});

it('rejects creating offer with missing required fields', function () {
    $user = User::factory()->create();

    actingAs($user);

    Volt::test('livewire.offers.create')
        ->set('title', '')
        ->set('description', '')
        ->set('required_skills', [])
        ->call('store')
        ->assertHasErrors(['title', 'description', 'required_skills']);
});

it('displays offer show page', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);

    actingAs($user)->get(route('offers.show', $offer))->assertOk();
});

it('updates an offer with valid data', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);

    actingAs($user);

    Volt::test('livewire.offers.edit', ['offer' => $offer])
        ->set('title', 'Updated Title')
        ->set('description', $offer->description)
        ->set('required_skills', $offer->required_skills)
        ->call('update')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('offers', [
        'id' => $offer->id,
        'title' => 'Updated Title',
    ]);
});

it('archives an offer', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);

    actingAs($user)->delete(route('offers.destroy', $offer));

    $this->assertSoftDeleted('offers', ['id' => $offer->id]);
});

it('displays archived offers page', function () {
    $user = User::factory()->create();
    actingAs($user)->get(route('offers.archived'))->assertOk();
});

it('restores an archived offer', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);
    $offer->delete();

    actingAs($user)->post(route('offers.restore', $offer));

    $this->assertDatabaseHas('offers', [
        'id' => $offer->id,
        'deleted_at' => null,
    ]);
});

it('force deletes an archived offer', function () {
    $user = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $user->id]);
    $offer->delete();

    actingAs($user)->delete(route('offers.forceDelete', $offer));

    $this->assertDatabaseMissing('offers', ['id' => $offer->id]);
});

it('prevents user from accessing other users offers', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user)->get(route('offers.show', $offer))->assertForbidden();
});

it('prevents user from editing other users offers', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user)->get(route('offers.edit', $offer))->assertForbidden();
});

it('prevents user from deleting other users offers', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $offer = Offer::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user)->delete(route('offers.destroy', $offer))->assertForbidden();
});

it('auto generates slug from title', function () {
    $offer = Offer::factory()->create(['title' => 'Développeur PHP Senior']);

    expect($offer->slug)->toBe('developpeur-php-senior');
});

it('generates unique slug for duplicate titles', function () {
    $offer1 = Offer::factory()->create(['title' => 'Développeur PHP']);
    $offer2 = Offer::factory()->create(['title' => 'Développeur PHP']);

    expect($offer1->slug)->toBe('developpeur-php');
    expect($offer2->slug)->toBe('developpeur-php-1');
});
