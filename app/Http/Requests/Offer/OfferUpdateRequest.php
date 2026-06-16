<?php

namespace App\Http\Requests\Offer;

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OfferUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:5000'],
            'responsibilities' => ['nullable', 'string'],
            'required_skills' => ['sometimes', 'required', 'array', 'min:1'],
            'required_skills.*' => ['string'],
            'soft_skills' => ['nullable', 'array'],
            'soft_skills.*' => ['string'],
            'min_experience_level' => ['nullable', Rule::enum(ExperienceLevel::class)],
            'education_level' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', Rule::enum(EmploymentType::class)],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title is required.',
            'description.required' => 'The description is required.',
            'description.max' => 'The description must not exceed 5000 characters.',
            'required_skills.required' => 'At least one required skill is required.',
            'required_skills.min' => 'At least one required skill is required.',
        ];
    }
}
