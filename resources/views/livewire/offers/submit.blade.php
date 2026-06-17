<?php

use App\Jobs\AnalyseCVJob;
use App\Jobs\ExtractCandidateInfoJob;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Document;
use App\Models\Offer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Offer $offer;

    #[Validate('required|file|mimes:pdf|max:10240')]
    public $cv = null;

    public ?string $cvPath = null;

    #[Validate('required')]
    public string $name = '';

    #[Validate('required|email')]
    public string $email = '';

    public ?string $phone = null;

    public ?string $address = null;

    public ?string $summary = null;

    public string $extractedText = '';

    public array $sections = [];

    public ?array $extractionPayload = null;

    public bool $isExtracting = false;

    public bool $hasWarning = false;

    public string $warningMessage = '';

    public ?string $extractionCacheKey = null;

    public int $pollCount = 0;

    public const MAX_POLL_ATTEMPTS = 30;

    public function mount(Offer $offer): void
    {
        $this->offer = $offer;
        $this->authorize('view', $this->offer);
    }

    public function uploadAndExtract(): void
    {
        $this->resetValidation();
        $this->hasWarning = false;
        $this->warningMessage = '';

        try {
            $this->validateOnly('cv');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('cv', $e->getMessage());
            return;
        }

        $filename = time().'_'.$this->cv->getClientOriginalName();
        $this->cvPath = $this->cv->storeAs('pdfs', $filename);

        $this->extractionCacheKey = 'cv_extraction_'.md5($this->cvPath);
        $this->isExtracting = true;
        $this->pollCount = 0;

        Cache::forget($this->extractionCacheKey);

        ExtractCandidateInfoJob::dispatch($this->cvPath, $this->extractionCacheKey);
    }

    public function pollExtraction(): void
    {
        if (! $this->extractionCacheKey) {
            return;
        }

        $this->pollCount++;

        $result = Cache::get($this->extractionCacheKey);

        if (! $result) {
            if ($this->pollCount >= self::MAX_POLL_ATTEMPTS) {
                $this->extractionCacheKey = null;
                $this->isExtracting = false;
                $this->hasWarning = true;
                $this->warningMessage = 'Extraction timed out. Please try again.';
            }

            return;
        }

        Cache::forget($this->extractionCacheKey);
        $this->extractionCacheKey = null;
        $this->isExtracting = false;

        if ($result['status'] === 'warning') {
            $this->hasWarning = true;
            $this->warningMessage = $result['message'];

            return;
        }

        if ($result['status'] === 'error') {
            $this->hasWarning = true;
            $this->warningMessage = $result['message'];

            return;
        }

        $this->extractedText = $result['extracted_text'] ?? '';
        $this->name = $result['name'] ?? '';
        $this->email = $result['email'] ?? '';
        $this->phone = $result['phone'] ?? null;
        $this->address = $result['address'] ?? null;
        $this->summary = $result['summary'] ?? null;
        $this->sections = $result['sections'] ?? [];
        $this->extractionPayload = $result['extraction_payload'] ?? null;
    }

    public function retryExtraction(): void
    {
        $this->hasWarning = false;
        $this->warningMessage = '';
        $this->isExtracting = true;
        $this->pollCount = 0;

        $this->extractionCacheKey = 'cv_extraction_'.md5($this->cvPath);

        Cache::forget($this->extractionCacheKey);

        ExtractCandidateInfoJob::dispatch($this->cvPath, $this->extractionCacheKey);
    }

    public function submit(): void
    {
        $this->validate([
            'name' => 'required',
            'email' => 'required|email',
            'cvPath' => 'required',
        ]);

        $existing = Application::where('offer_id', $this->offer->id)
            ->whereHas('candidate', fn ($q) => $q->where('email', $this->email))
            ->exists();

        if ($existing) {
            $this->addError('email', 'You have already submitted a CV for this offer.');

            return;
        }

        $candidate = Candidate::updateOrCreate(
            ['email' => $this->email, 'user_id' => Auth::id()],
            [
                'name' => $this->name,
                'phone' => $this->phone,
                'address' => $this->address,
                'summary' => $this->summary,
                'extracted_text' => $this->extractedText,
                'extraction_payload' => $this->extractionPayload,
            ]
        );

        $document = Document::create([
            'user_id' => Auth::id(),
            'title' => $this->name,
            'filename' => basename($this->cvPath),
            'original_path' => $this->cvPath,
            'metadata' => ['sections' => $this->sections],
        ]);

        $application = Application::create([
            'user_id' => Auth::id(),
            'candidate_id' => $candidate->id,
            'offer_id' => $this->offer->id,
            'document_id' => $document->id,
        ]);

        AnalyseCVJob::dispatch($application->id);

        session()->flash('success', "CV submitted successfully for {$this->name}");

        $this->redirect(route('offers.show', $this->offer));
    }
} ?>

<div @if($isExtracting) wire:poll.2s="pollExtraction" @endif>
    <div class="mb-6">
        <flux:button href="{{ route('offers.show', $offer) }}" variant="ghost" size="sm" class="mb-4">
            ← Back to offer
        </flux:button>

        <flux:heading size="lg">Submit CV for {{ $offer->title }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500">Upload a PDF CV to extract candidate information and submit it for this offer.</flux:text>
    </div>

    <div class="space-y-6">
        <flux:card>
            <div class="space-y-4">
                <flux:heading size="sm">CV File</flux:heading>

                <div>
                    <flux:input wire:model="cv" type="file" label="Select PDF" accept=".pdf" />
                    <flux:error name="cv" />
                </div>

                @if ($cv && !$cvPath)
                    <flux:button wire:click="uploadAndExtract" variant="primary" :disabled="$isExtracting">
                        @if ($isExtracting)
                            <svg class="animate-spin size-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Extracting...
                        @else
                            Upload & Extract
                        @endif
                    </flux:button>
                @endif

                @if ($isExtracting)
                    <div class="flex items-center gap-2 text-zinc-500">
                        <svg class="animate-spin size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm">Extracting candidate information...</span>
                    </div>
                @endif

                @if ($cvPath && !$isExtracting)
                    <div class="flex items-center gap-2 text-emerald-600">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-sm">CV uploaded successfully</span>
                    </div>
                @endif

                @if ($hasWarning)
                    <div class="rounded-lg bg-amber-50 p-4 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                        <div class="flex items-start justify-between gap-3">
                            <span>{{ $warningMessage }}</span>
                            <flux:button wire:click="retryExtraction" variant="primary" size="sm" :disabled="$isExtracting">
                                @if ($isExtracting)
                                    <svg class="animate-spin size-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Retrying...
                                @else
                                    Retry
                                @endif
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>
        </flux:card>

        @if ($cvPath && !$isExtracting)
            <form wire:submit="submit" class="space-y-6">
                <flux:card>
                    <div class="space-y-4">
                        <flux:heading size="sm">Candidate Information</flux:heading>

                        <flux:input wire:model="name" label="Name" placeholder="John Doe" required />
                        <flux:error name="name" />

                        <flux:input wire:model="email" label="Email" placeholder="john@example.com" required />
                        <flux:error name="email" />

                        <flux:input wire:model="phone" label="Phone" placeholder="+1 234 567 890" />
                        <flux:error name="phone" />

                        <flux:input wire:model="address" label="Address" placeholder="City, Country" />
                        <flux:error name="address" />

                        <flux:textarea wire:model="summary" label="Professional Summary" rows="3" />
                        <flux:error name="summary" />
                    </div>
                </flux:card>

                @if (count($sections) > 0)
                    <flux:card>
                        <div class="space-y-4">
                            <flux:heading size="sm">Extracted CV Sections</flux:heading>

                            @foreach ($sections as $section)
                                <div class="border-b border-zinc-200 pb-4 last:border-0 last:pb-0">
                                    <flux:text class="font-medium text-zinc-700 dark:text-zinc-300">{{ $section['title'] ?? 'Section' }}</flux:text>
                                    <div class="mt-2 space-y-1">
                                        @foreach ($section['items'] ?? [] as $item)
                                            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">• {{ $item }}</flux:text>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </flux:card>
                @endif

                <div class="flex items-center justify-end gap-3">
                    <flux:button href="{{ route('offers.show', $offer) }}" variant="subtle">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Submit CV</flux:button>
                </div>
            </form>
        @endif
    </div>
</div>
