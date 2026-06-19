<?php

namespace Database\Factories;

use App\Enums\ProcessStatus;
use App\Enums\Recommandation;
use App\Models\Analysis;
use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Analysis> */
class AnalysisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'user_id' => User::factory(),
            'matching_score' => fake()->numberBetween(0, 100),
            'recommendation' => fake()->randomElement([Recommandation::Shortlisted, Recommandation::OnHold, Recommandation::Rejected]),
            'extracted_skills' => fake()->randomElements(['PHP', 'Laravel', 'MySQL', 'Git', 'Docker'], 3),
            'missing_skills' => fake()->randomElements(['AWS', 'Redis', 'Vue.js', 'Tailwind'], 2),
            'strengths' => fake()->paragraph(),
            'gaps' => fake()->paragraph(),
            'justification' => fake()->paragraph(),
            'raw_response' => json_encode([
                'matching_score' => fake()->numberBetween(0, 100),
                'extracted_skills' => fake()->randomElements(['PHP', 'Laravel', 'MySQL', 'Git', 'Docker'], 3),
                'missing_skills' => fake()->randomElements(['AWS', 'Redis', 'Vue.js', 'Tailwind'], 2),
                'strengths' => fake()->paragraph(),
                'gaps' => fake()->paragraph(),
                'justification' => fake()->paragraph(),
                'recommendation' => fake()->randomElement(['shortlisted', 'on_hold', 'rejected']),
            ]),
            'status' => ProcessStatus::Processed,
        ];
    }
}
