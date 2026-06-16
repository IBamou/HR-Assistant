<?php

use App\Models\Offer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;

new class extends Component {
    use AuthorizesRequests;

    public Offer $offer;
    public bool $showArchiveModal = false;

    public function mount(Offer $offer): void
    {
        $this->offer = $offer;
        $this->authorize('view', $this->offer);
    }

    public function archive(): void
    {
        $this->authorize('delete', $this->offer);

        $this->offer->delete();

        $this->redirect(route('offers.index'));
    }
} ?>

<div>
    <div class="mb-6">
        <flux:button href="{{ route('offers.index') }}" variant="ghost" size="sm" class="mb-4">
            ← Back to offers
        </flux:button>

        <div class="flex items-start justify-between">
            <div>
                <flux:heading size="lg">{{ $offer->title }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500">Created {{ $offer->created_at->diffForHumans() }}</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <flux:button href="{{ route('offers.edit', $offer) }}" variant="primary" size="sm">
                    Edit
                </flux:button>
                <flux:button wire:click="$set('showArchiveModal', true)" variant="danger" size="sm">
                    Archive
                </flux:button>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <flux:card>
                <flux:heading size="sm" class="mb-4">Description</flux:heading>
                <flux:text class="whitespace-pre-wrap">{{ $offer->description }}</flux:text>
            </flux:card>

            @if ($offer->responsibilities)
                <flux:card>
                    <flux:heading size="sm" class="mb-4">Responsibilities</flux:heading>
                    <flux:text class="whitespace-pre-wrap">{{ $offer->responsibilities }}</flux:text>
                </flux:card>
            @endif

            <flux:card>
                <flux:heading size="sm" class="mb-4">Applications</flux:heading>
                <flux:text class="text-zinc-500">No applications yet. Applications will appear here once CVs are analyzed.</flux:text>
            </flux:card>
        </div>

        <div class="space-y-6">
            <flux:card>
                <flux:heading size="sm" class="mb-4">Details</flux:heading>

                <div class="space-y-3">
                    @if ($offer->employment_type)
                        <div class="flex items-center justify-between">
                            <flux:text class="text-sm text-zinc-500">Employment Type</flux:text>
                            <flux:badge color="zinc" size="sm">{{ $offer->employment_type->label() }}</flux:badge>
                        </div>
                    @endif

                    @if ($offer->min_experience_level)
                        <div class="flex items-center justify-between">
                            <flux:text class="text-sm text-zinc-500">Experience</flux:text>
                            <flux:badge color="violet" size="sm">{{ $offer->min_experience_level->label() }}</flux:badge>
                        </div>
                    @endif

                    @if ($offer->location)
                        <div class="flex items-center justify-between">
                            <flux:text class="text-sm text-zinc-500">Location</flux:text>
                            <flux:text class="text-sm">{{ $offer->location }}</flux:text>
                        </div>
                    @endif

                    @if ($offer->education_level)
                        <div class="flex items-center justify-between">
                            <flux:text class="text-sm text-zinc-500">Education</flux:text>
                            <flux:text class="text-sm">{{ $offer->education_level }}</flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>

            @if (count($offer->required_skills ?? []) > 0)
                <flux:card>
                    <flux:heading size="sm" class="mb-4">Required Skills</flux:heading>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($offer->required_skills as $skill)
                            <flux:badge color="indigo" size="sm">{{ $skill }}</flux:badge>
                        @endforeach
                    </div>
                </flux:card>
            @endif

            @if (count($offer->soft_skills ?? []) > 0)
                <flux:card>
                    <flux:heading size="sm" class="mb-4">Soft Skills</flux:heading>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($offer->soft_skills as $skill)
                            <flux:badge color="emerald" size="sm">{{ $skill }}</flux:badge>
                        @endforeach
                    </div>
                </flux:card>
            @endif
        </div>
    </div>

    <flux:modal wire:model="showArchiveModal">
        <flux:heading size="sm">Archive Offer</flux:heading>
        <flux:text class="mt-2">Are you sure you want to archive this offer? It will no longer appear in the active list.</flux:text>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button wire:click="$set('showArchiveModal', false)" variant="subtle">Cancel</flux:button>
            <flux:button wire:click="archive" variant="danger">Archive</flux:button>
        </div>
    </flux:modal>
</div>
