<?php

use App\Models\Analysis;
use App\Models\Application;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;

new class extends Component {
    use AuthorizesRequests;

    public Offer $offer;
    public bool $showArchiveModal = false;
    public bool $showAnalysisModal = false;
    public bool $showRemoveAppModal = false;
    public ?Analysis $selectedAnalysis = null;
    public ?int $removingApplicationId = null;

    /** @var Collection<int, \App\Models\Application> */
    public Collection $applications;

    public function mount(Offer $offer): void
    {
        $this->offer = $offer;
        $this->authorize('view', $this->offer);
        $this->applications = $this->offer->applications()
            ->ownedByCurrentUser()
            ->with('candidate', 'analysis')
            ->get();
    }

    public function viewAnalysis(int $analysisId): void
    {
        $this->selectedAnalysis = $this->applications
            ->pluck('analysis')
            ->filter()
            ->firstWhere('id', $analysisId);

        if ($this->selectedAnalysis) {
            $this->showAnalysisModal = true;
        }
    }

    public function confirmRemoveApplication(int $applicationId): void
    {
        $this->removingApplicationId = $applicationId;
        $this->showRemoveAppModal = true;
    }

    public function removeApplication(): void
    {
        $application = Application::ownedByCurrentUser()->findOrFail($this->removingApplicationId);

        $application->analysis?->delete();
        $application->delete();

        $this->showRemoveAppModal = false;
        $this->removingApplicationId = null;

        $this->applications = $this->offer->applications()
            ->ownedByCurrentUser()
            ->with('candidate', 'analysis')
            ->get();
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
                <flux:button href="{{ route('offers.submit', $offer) }}" variant="primary" size="sm">
                    Submit CV
                </flux:button>
                <flux:button href="{{ route('offers.edit', $offer) }}" variant="subtle" size="sm">
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
                @if ($applications->isEmpty())
                    <flux:text class="text-zinc-500">No applications yet. Applications will appear here once CVs are submitted.</flux:text>
                @else
                    <div class="space-y-3">
                        @foreach ($applications as $application)
                            @php
                                $analysis = $application->analysis;
                            @endphp
                            <div
                                @if ($analysis)
                                    wire:click="viewAnalysis({{ $analysis->id }})"
                                    class="flex cursor-pointer items-center justify-between border-b border-zinc-200 pb-3 last:border-0 last:pb-0 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/50"
                                @else
                                    class="flex items-center justify-between border-b border-zinc-200 pb-3 last:border-0 last:pb-0 dark:border-zinc-700"
                                @endif
                            >
                                <div>
                                    <flux:text class="font-medium">{{ $application->candidate->name }}</flux:text>
                                    <flux:text class="text-sm text-zinc-500">{{ $application->candidate->email }}</flux:text>
                                </div>
                                <div class="flex items-center gap-3">
                                    <flux:button href="{{ route('applications.show', $application) }}" wire:navigate @click.stop size="xs" variant="ghost" class="text-zinc-400 hover:text-zinc-600">
                                        View
                                    </flux:button>
                                    @if ($analysis && $analysis->status === \App\Enums\ProcessStatus::Pending)
                                        <flux:badge color="amber" size="sm">Pending</flux:badge>
                                    @elseif ($analysis && $analysis->status === \App\Enums\ProcessStatus::Processed)
                                        <flux:badge color="green" size="sm">{{ $analysis->matching_score }}%</flux:badge>
                                    @elseif ($analysis && $analysis->status === \App\Enums\ProcessStatus::Failed)
                                        <flux:badge color="red" size="sm">Failed</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">No analysis</flux:badge>
                                    @endif
                                    <flux:text class="text-xs text-zinc-400">{{ $application->created_at->diffForHumans() }}</flux:text>
                                    <flux:button wire:click.stop="confirmRemoveApplication({{ $application->id }})" size="xs" variant="subtle" icon="trash" class="text-zinc-400 hover:text-red-600"></flux:button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
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

    <flux:modal wire:model="showRemoveAppModal">
        <flux:heading size="sm">Remove Application</flux:heading>
        <flux:text class="mt-2">Are you sure you want to remove this application? The analysis data will also be deleted. This action cannot be undone.</flux:text>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button wire:click="$set('showRemoveAppModal', false)" variant="subtle">Cancel</flux:button>
            <flux:button wire:click="removeApplication" variant="danger">Remove</flux:button>
        </div>
    </flux:modal>

    @if ($selectedAnalysis)
        <flux:modal wire:model="showAnalysisModal">
            <flux:heading size="sm">CV Analysis — {{ $selectedAnalysis->application->candidate->name }}</flux:heading>

            <div class="mt-4 space-y-4">
                <div class="flex items-center gap-3">
                    @php
                        $badgeColor = match ($selectedAnalysis->recommendation) {
                            \App\Enums\Recommandation::Shortlisted => 'green',
                            \App\Enums\Recommandation::OnHold => 'amber',
                            default => 'red',
                        };
                    @endphp
                    <flux:heading size="lg">{{ $selectedAnalysis->matching_score }}%</flux:heading>
                    <flux:badge color="{{ $badgeColor }}" size="sm">
                        {{ $selectedAnalysis->recommendation?->label() }}
                    </flux:badge>
                </div>

                <div>
                    <flux:heading size="sm" class="mb-1">Strengths</flux:heading>
                    <flux:text class="whitespace-pre-wrap">{{ $selectedAnalysis->strengths }}</flux:text>
                </div>

                <div>
                    <flux:heading size="sm" class="mb-1">Gaps</flux:heading>
                    <flux:text class="whitespace-pre-wrap">{{ $selectedAnalysis->gaps }}</flux:text>
                </div>

                <div>
                    <flux:heading size="sm" class="mb-1">Justification</flux:heading>
                    <flux:text class="whitespace-pre-wrap">{{ $selectedAnalysis->justification }}</flux:text>
                </div>

                @if (count($selectedAnalysis->extracted_skills ?? []) > 0)
                    <div>
                        <flux:heading size="sm" class="mb-1">Extracted Skills</flux:heading>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($selectedAnalysis->extracted_skills as $skill)
                                <flux:badge color="indigo" size="sm">{{ $skill }}</flux:badge>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (count($selectedAnalysis->missing_skills ?? []) > 0)
                    <div>
                        <flux:heading size="sm" class="mb-1">Missing Skills</flux:heading>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($selectedAnalysis->missing_skills as $skill)
                                <flux:badge color="red" size="sm">{{ $skill }}</flux:badge>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($selectedAnalysis->raw_response)
                    <div x-data="{ open: false }">
                        <button @click="open = !open" type="button" class="flex items-center gap-2 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                            <span x-show="!open">▶</span><span x-show="open" x-cloak>▼</span>
                            Raw AI Response
                        </button>
                        <pre x-show="open" x-cloak class="mt-2 max-h-64 overflow-auto rounded bg-zinc-100 p-3 text-xs dark:bg-zinc-800">{{ $selectedAnalysis->raw_response }}</pre>
                    </div>
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <flux:button href="{{ route('applications.show', $selectedAnalysis->application) }}" wire:navigate variant="primary" size="sm">
                    View Full Analysis
                </flux:button>
                <flux:button wire:click="$set('showAnalysisModal', false)" variant="subtle">Close</flux:button>
            </div>
        </flux:modal>
    @endif
</div>
