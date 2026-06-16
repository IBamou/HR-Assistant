<?php

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Http\Requests\Offer\OfferUpdateRequest;
use App\Models\Offer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;

new class extends Component {
    use AuthorizesRequests;

    public Offer $offer;
    public string $title = '';
    public string $description = '';
    public ?string $responsibilities = null;
    public array $required_skills = [];
    public ?string $new_skill = null;
    public array $soft_skills = [];
    public ?string $new_soft_skill = null;
    public ?ExperienceLevel $min_experience_level = null;
    public ?string $education_level = null;
    public ?EmploymentType $employment_type = null;
    public ?string $location = null;

    public function mount(Offer $offer): void
    {
        $this->offer = $offer;
        $this->authorize('update', $this->offer);

        $this->title = $offer->title;
        $this->description = $offer->description;
        $this->responsibilities = $offer->responsibilities;
        $this->required_skills = $offer->required_skills ?? [];
        $this->soft_skills = $offer->soft_skills ?? [];
        $this->min_experience_level = $offer->min_experience_level;
        $this->education_level = $offer->education_level;
        $this->employment_type = $offer->employment_type;
        $this->location = $offer->location;
    }

    public function addRequiredSkill(): void
    {
        $skill = trim($this->new_skill ?? '');
        if ($skill !== '' && !in_array($skill, $this->required_skills)) {
            $this->required_skills[] = $skill;
            $this->new_skill = null;
        }
    }

    public function removeRequiredSkill(string $skill): void
    {
        $this->required_skills = array_values(array_filter($this->required_skills, fn ($s) => $s !== $skill));
    }

    public function addSoftSkill(): void
    {
        $skill = trim($this->new_soft_skill ?? '');
        if ($skill !== '' && !in_array($skill, $this->soft_skills)) {
            $this->soft_skills[] = $skill;
            $this->new_soft_skill = null;
        }
    }

    public function removeSoftSkill(string $skill): void
    {
        $this->soft_skills = array_values(array_filter($this->soft_skills, fn ($s) => $s !== $skill));
    }

    public function update(): void
    {
        $validated = $this->validate((new OfferUpdateRequest)->rules());

        if ($validated['min_experience_level'] && is_string($validated['min_experience_level'])) {
            $validated['min_experience_level'] = ExperienceLevel::tryFrom($validated['min_experience_level']);
        }
        if ($validated['employment_type'] && is_string($validated['employment_type'])) {
            $validated['employment_type'] = EmploymentType::tryFrom($validated['employment_type']);
        }

        $this->offer->update($validated);

        $this->redirect(route('offers.show', $this->offer));
    }
} ?>

<div>
    <div class="mb-6">
        <flux:button href="{{ route('offers.show', $offer) }}" variant="ghost" size="sm" class="mb-4">
            ← Back to offer
        </flux:button>

        <flux:heading size="lg">Edit Offer</flux:heading>
        <flux:text class="mt-1 text-zinc-500">Update the information for this job offer.</flux:text>
    </div>

    <form wire:submit="update" class="space-y-6">
        <flux:card>
            <div class="space-y-4">
                <flux:input wire:model="title" label="Title" placeholder="Senior PHP Developer" required />
                <flux:error name="title" />

                <flux:textarea wire:model="description" label="Description" placeholder="Job description..." rows="4" required />
                <flux:error name="description" />

                <flux:textarea wire:model="responsibilities" label="Responsibilities" placeholder="Daily tasks, projects..." rows="4" />
                <flux:error name="responsibilities" />

                <flux:input wire:model="education_level" label="Education Level" placeholder="Master's, Bachelor's, etc." />
                <flux:error name="education_level" />

                <flux:input wire:model="location" label="Location" placeholder="Casablanca, Morocco / Remote" />
                <flux:error name="location" />
            </div>
        </flux:card>

        <flux:card>
            <div class="space-y-4">
                <flux:heading size="sm">Required Skills</flux:heading>

                <div class="flex gap-2">
                    <flux:input wire:model="new_skill" placeholder="Add a skill (e.g. PHP, Laravel)" class="flex-1" />
                    <flux:button type="button" wire:click="addRequiredSkill" variant="primary">Add</flux:button>
                </div>

                @if (count($required_skills) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach ($required_skills as $skill)
                            <flux:badge color="indigo" size="sm">
                                {{ $skill }}
                                <button type="button" wire:click="removeRequiredSkill('{{ $skill }}')" class="ml-1 text-indigo-300 hover:text-white">&times;</button>
                            </flux:badge>
                        @endforeach
                    </div>
                @endif
            </div>
        </flux:card>

        <flux:card>
            <div class="space-y-4">
                <flux:heading size="sm">Soft Skills</flux:heading>

                <div class="flex gap-2">
                    <flux:input wire:model="new_soft_skill" placeholder="Add a skill (e.g. Communication)" class="flex-1" />
                    <flux:button type="button" wire:click="addSoftSkill" variant="primary">Add</flux:button>
                </div>

                @if (count($soft_skills) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach ($soft_skills as $skill)
                            <flux:badge color="emerald" size="sm">
                                {{ $skill }}
                                <button type="button" wire:click="removeSoftSkill('{{ $skill }}')" class="ml-1 text-emerald-300 hover:text-white">&times;</button>
                            </flux:badge>
                        @endforeach
                    </div>
                @endif
            </div>
        </flux:card>

        <flux:card>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <flux:select wire:model="min_experience_level" label="Experience Level">
                        <option value="">Select</option>
                        @foreach (ExperienceLevel::cases() as $level)
                            <option value="{{ $level->value }}">{{ $level->label() }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="min_experience_level" />
                </div>

                <div>
                    <flux:select wire:model="employment_type" label="Employment Type">
                        <option value="">Select</option>
                        @foreach (EmploymentType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="employment_type" />
                </div>
            </div>
        </flux:card>

        <div class="flex items-center justify-end gap-3">
            <flux:button href="{{ route('offers.show', $offer) }}" variant="subtle">Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
