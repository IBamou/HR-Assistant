<?php

use App\Enums\ProcessStatus;
use App\Enums\Recommandation;
use App\Ai\Agents\CvScorer;
use App\Models\Analysis;
use App\Models\Application;
use App\Services\AiClient;
use Livewire\Volt\Component;

new class extends Component {
    public Application $application;
    public bool $showRemoveModal = false;

    public function mount(Application $application): void
    {
        $this->application = $application->load(['candidate', 'offer', 'analysis']);

        abort_if($this->application->user_id !== auth()->id(), 403);
    }

    public function reAnalyse(): void
    {
        $cvText = $this->application->candidate?->extracted_text;

        if (! $cvText) {
            session()->flash('error', 'No extracted CV text available for this candidate.');

            return;
        }

        $this->application->analysis?->delete();
        $this->application->unsetRelation('analysis');

        $offer = $this->application->offer;

        $agent = new CvScorer(
            offerTitle: $offer->title,
            offerDescription: $offer->description,
            requiredSkills: implode(', ', $offer->required_skills ?? []),
        );

        try {
            $response = app(AiClient::class)->prompt($agent, $cvText);

            $data = AiClient::parseResponse($response);

            if (! $data) {
                throw new \RuntimeException('Failed to parse AI response as JSON');
            }

            Analysis::create([
                'application_id' => $this->application->id,
                'user_id' => $this->application->user_id,
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
            Analysis::create([
                'application_id' => $this->application->id,
                'user_id' => $this->application->user_id,
                'status' => ProcessStatus::Failed,
            ]);
        }

        $this->application->load('analysis');
    }

    public function removeApplication(): void
    {
        $offer = $this->application->offer;

        $this->application->analysis?->delete();
        $this->application->delete();

        $this->redirect(route('offers.show', $offer), navigate: true);
    }
} ?>

<div>
    <div class="mb-6">
        <flux:button href="{{ route('offers.show', $this->application->offer) }}" variant="ghost" size="sm" class="mb-4">
            ← Back to {{ $this->application->offer->title }}
        </flux:button>

        <div class="flex items-start justify-between">
            <div>
                <flux:heading size="lg">{{ $this->application->candidate->name }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    Application for {{ $this->application->offer->title }} · Submitted {{ $this->application->created_at->diffForHumans() }}
                </flux:text>
            </div>
            <div class="flex items-center gap-2">
                @if ($this->application->analysis)
                    <flux:button wire:click="reAnalyse" variant="outline" size="sm">
                        Re-analyse
                    </flux:button>
                @else
                    <flux:button wire:click="reAnalyse" variant="primary" size="sm">
                        Analyse CV
                    </flux:button>
                @endif
                <flux:button wire:click="$toggle('showRemoveModal')" variant="danger" size="sm">
                    Remove
                </flux:button>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1 space-y-6">
            <flux:card>
                <flux:heading size="sm" class="mb-4">Candidate Details</flux:heading>

                <div class="space-y-3">
                    <div>
                        <flux:text class="text-sm text-zinc-500">Name</flux:text>
                        <flux:text>{{ $this->application->candidate->name }}</flux:text>
                    </div>

                    @if ($this->application->candidate->email)
                        <div>
                            <flux:text class="text-sm text-zinc-500">Email</flux:text>
                            <flux:text>{{ $this->application->candidate->email }}</flux:text>
                        </div>
                    @endif

                    @if ($this->application->candidate->phone)
                        <div>
                            <flux:text class="text-sm text-zinc-500">Phone</flux:text>
                            <flux:text>{{ $this->application->candidate->phone }}</flux:text>
                        </div>
                    @endif

                    @if ($this->application->candidate->address)
                        <div>
                            <flux:text class="text-sm text-zinc-500">Address</flux:text>
                            <flux:text>{{ $this->application->candidate->address }}</flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>

            @if ($this->application->candidate->summary)
                <flux:card>
                    <flux:heading size="sm" class="mb-4">Professional Summary</flux:heading>
                    <flux:text class="whitespace-pre-wrap text-sm">{{ $this->application->candidate->summary }}</flux:text>
                </flux:card>
            @endif

            @if ($this->application->candidate->extracted_text)
                <flux:card>
                    <details>
                        <summary class="cursor-pointer text-sm font-medium text-zinc-600">Extracted CV Text</summary>
                        <flux:text class="mt-2 whitespace-pre-wrap text-xs">{{ $this->application->candidate->extracted_text }}</flux:text>
                    </details>
                </flux:card>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-6">
            @php $analysis = $this->application->analysis; @endphp

            @if (! $analysis)
                <flux:card>
                    <flux:heading size="sm" class="mb-2">CV Analysis</flux:heading>
                    <flux:text class="text-zinc-500">No analysis has been run for this application yet. Click "Analyse CV" to get started.</flux:text>
                </flux:card>
            @elseif ($analysis->status === \App\Enums\ProcessStatus::Pending)
                <flux:card>
                    <div class="flex items-center gap-3">
                        <flux:badge color="amber" size="sm">Pending</flux:badge>
                        <flux:text class="text-zinc-500">Analysis is in progress.</flux:text>
                    </div>
                </flux:card>
            @elseif ($analysis->status === \App\Enums\ProcessStatus::Failed)
                <flux:card>
                    <div class="flex items-center gap-3">
                        <flux:badge color="red" size="sm">Failed</flux:badge>
                        <flux:text class="text-zinc-500">The analysis failed. Click "Re-analyse" to try again.</flux:text>
                    </div>
                </flux:card>
            @elseif ($analysis->status === \App\Enums\ProcessStatus::Processed)
                <flux:card>
                    <flux:heading size="sm" class="mb-4">Analysis Results</flux:heading>

                    <div class="flex items-center gap-3 mb-6">
                        <flux:heading size="xl">{{ $analysis->matching_score }}%</flux:heading>
                        @php
                            $badgeColor = match ($analysis->recommendation) {
                                \App\Enums\Recommandation::Shortlisted => 'green',
                                \App\Enums\Recommandation::OnHold => 'amber',
                                default => 'red',
                            };
                        @endphp
                        <flux:badge color="{{ $badgeColor }}" size="sm">
                            {{ $analysis->recommendation?->label() }}
                        </flux:badge>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <flux:heading size="sm" class="mb-1">Strengths</flux:heading>
                            <flux:text class="whitespace-pre-wrap text-sm">{{ $analysis->strengths }}</flux:text>
                        </div>
                        <div>
                            <flux:heading size="sm" class="mb-1">Gaps</flux:heading>
                            <flux:text class="whitespace-pre-wrap text-sm">{{ $analysis->gaps }}</flux:text>
                        </div>
                    </div>

                    <div class="mt-4">
                        <flux:heading size="sm" class="mb-1">Justification</flux:heading>
                        <flux:text class="whitespace-pre-wrap text-sm">{{ $analysis->justification }}</flux:text>
                    </div>

                    @if (count($analysis->extracted_skills ?? []) > 0 || count($analysis->missing_skills ?? []) > 0)
                        <flux:separator class="my-4" />

                        <div class="grid gap-4 sm:grid-cols-2">
                            @if (count($analysis->extracted_skills ?? []) > 0)
                                <div>
                                    <flux:heading size="sm" class="mb-2">Extracted Skills</flux:heading>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($analysis->extracted_skills as $skill)
                                            <flux:badge color="indigo" size="sm">{{ $skill }}</flux:badge>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if (count($analysis->missing_skills ?? []) > 0)
                                <div>
                                    <flux:heading size="sm" class="mb-2">Missing Skills</flux:heading>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($analysis->missing_skills as $skill)
                                            <flux:badge color="red" size="sm">{{ $skill }}</flux:badge>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($analysis->raw_response)
                        <flux:separator class="my-4" />

                        <div x-data="{ open: false }">
                            <button @click="open = !open" type="button" class="flex items-center gap-2 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                                <span x-show="!open">▶</span><span x-show="open" x-cloak>▼</span>
                                Raw AI Response
                            </button>
                            <pre x-show="open" x-cloak class="mt-2 max-h-64 overflow-auto rounded bg-zinc-100 p-3 text-xs dark:bg-zinc-800">{{ $analysis->raw_response }}</pre>
                        </div>
                    @endif
                </flux:card>
            @endif
        </div>
    </div>

    <flux:modal wire:model="showRemoveModal">
        <flux:heading size="sm">Remove Application</flux:heading>
        <flux:text class="mt-2">Are you sure you want to remove {{ $this->application->candidate->name }} from {{ $this->application->offer->title }}? The analysis data will also be deleted.</flux:text>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button wire:click="$set('showRemoveModal', false)" variant="subtle">Cancel</flux:button>
            <flux:button wire:click="removeApplication" variant="danger">Remove</flux:button>
        </div>
    </flux:modal>
</div>
