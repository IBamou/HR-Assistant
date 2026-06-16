<?php

use App\Models\Offer;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public ?int $offerToDelete = null;

    public function archivedOffers()
    {
        return Auth::user()->offers()
            ->onlyTrashed()
            ->latest('deleted_at')
            ->paginate(12);
    }

    public function restore(Offer $offer): void
    {
        $this->authorize('restore', $offer);

        $offer->restore();

        $this->dispatch('archived-updated');
    }

    public function confirmForceDelete(int $offerId): void
    {
        $this->offerToDelete = $offerId;
    }

    public function forceDelete(): void
    {
        if ($this->offerToDelete === null) {
            return;
        }

        $offer = Offer::onlyTrashed()->findOrFail($this->offerToDelete);

        $this->authorize('forceDelete', $offer);

        $offer->forceDelete();
        $this->offerToDelete = null;

        $this->dispatch('archived-updated');
    }
} ?>

<div>
    <div class="mb-6">
        <flux:heading size="lg">Archived Offers</flux:heading>
        <flux:text class="mt-1 text-zinc-500">Manage archived offers — restore or permanently delete them.</flux:text>
    </div>

    @php $archivedOffers = $this->archivedOffers(); @endphp

    @if ($archivedOffers->isEmpty())
        <flux:card class="py-12 text-center">
            <flux:icon.archive-box class="mx-auto size-12 text-zinc-400" />
            <flux:heading size="sm" class="mt-4">No archived offers</flux:heading>
            <flux:text class="mt-2 text-zinc-500">Archived offers will appear here.</flux:text>
        </flux:card>
    @else
        <div class="space-y-4">
            @foreach ($archivedOffers as $offer)
                <flux:card class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div>
                            <flux:heading size="sm">{{ $offer->title }}</flux:heading>
                            <flux:text class="text-sm text-zinc-500">
                                Archived {{ $offer->deleted_at->diffForHumans() }}
                                @if ($offer->location) · {{ $offer->location }} @endif
                            </flux:text>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <flux:button wire:click="restore({{ $offer->id }})" variant="primary" size="sm">
                            Restore
                        </flux:button>
                        <flux:button wire:click="confirmForceDelete({{ $offer->id }})" variant="danger" size="sm">
                            Delete
                        </flux:button>
                    </div>
                </flux:card>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $archivedOffers->links() }}
        </div>
    @endif

    <flux:modal wire:model.live="offerToDelete" :show="$offerToDelete !== null">
        <flux:heading size="sm">Permanently Delete</flux:heading>
        <flux:text class="mt-2">This action is irreversible. The offer will be permanently deleted from the database.</flux:text>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button wire:click="$set('offerToDelete', null)" variant="subtle">Cancel</flux:button>
            <flux:button wire:click="forceDelete" variant="danger">Permanently Delete</flux:button>
        </div>
    </flux:modal>
</div>
