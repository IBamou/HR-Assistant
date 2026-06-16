<?php

use App\Models\Offer;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function offers()
    {
        return Auth::user()->offers()
            ->when($this->search, fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))
            ->latest()
            ->paginate(12);
    }
} ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="lg">Job Offers</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Manage your job offers and track applications.</flux:text>
        </div>
        <flux:button href="{{ route('offers.create') }}" variant="primary">
            <flux:icon.plus class="size-4" />
            New Offer
        </flux:button>
    </div>

    <div class="mb-6">
        <flux:input
            wire:model.live="search"
            placeholder="Search offers..."
            icon="magnifying-glass"
        />
    </div>

    @php $offers = $this->offers(); @endphp

    @if ($offers->isEmpty())
        <flux:card class="py-12 text-center">
            <flux:icon.document-text class="mx-auto size-12 text-zinc-400" />
            <flux:heading size="sm" class="mt-4">No offers</flux:heading>
            <flux:text class="mt-2 text-zinc-500">Get started by creating your first job offer.</flux:text>
            <flux:button href="{{ route('offers.create') }}" variant="primary" class="mt-4">
                Create Offer
            </flux:button>
        </flux:card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($offers as $offer)
                <flux:card class="hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between">
                        <flux:heading size="sm">
                            <a href="{{ route('offers.show', $offer) }}" class="hover:text-indigo-600 transition-colors">
                                {{ $offer->title }}
                            </a>
                        </flux:heading>
                        @if ($offer->employment_type)
                            <flux:badge color="zinc" size="sm">{{ $offer->employment_type->label() }}</flux:badge>
                        @endif
                    </div>

                    @if ($offer->location)
                        <flux:text class="mt-2 flex items-center gap-1 text-sm text-zinc-500">
                            <flux:icon.map-pin class="size-4" />
                            {{ $offer->location }}
                        </flux:text>
                    @endif

                    @if (count($offer->required_skills ?? []) > 0)
                        <div class="mt-3 flex flex-wrap gap-1">
                            @foreach (array_slice($offer->required_skills, 0, 3) as $skill)
                                <flux:badge color="indigo" size="sm">{{ $skill }}</flux:badge>
                            @endforeach
                            @if (count($offer->required_skills) > 3)
                                <flux:badge color="zinc" size="sm">+{{ count($offer->required_skills) - 3 }}</flux:badge>
                            @endif
                        </div>
                    @endif

                    <div class="mt-4 flex items-center justify-between border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:text class="text-xs text-zinc-400">
                            {{ $offer->created_at->diffForHumans() }}
                        </flux:text>
                        <flux:button href="{{ route('offers.show', $offer) }}" variant="ghost" size="sm">
                            View →
                        </flux:button>
                    </div>
                </flux:card>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $offers->links() }}
        </div>
    @endif
</div>
