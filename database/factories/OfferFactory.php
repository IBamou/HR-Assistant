<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->jobTitle();

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'description' => fake()->paragraph(3),
            'responsibilities' => fake()->paragraph(2),
            'required_skills' => collect(random_int(2, 5))
                ->map(fn () => fake()->word())
                ->unique()
                ->values()
                ->all(),
            'soft_skills' => collect(random_int(1, 3))
                ->map(fn () => fake()->randomElement(['Communication', 'Leadership', 'Teamwork', 'Problem Solving', 'Creativity', 'Adaptability']))
                ->unique()
                ->values()
                ->all(),
            'min_experience_level' => fake()->randomElement(ExperienceLevel::cases()),
            'education_level' => fake()->randomElement(['Licence', 'Master', 'Doctorat', 'BTS', 'DUT']),
            'employment_type' => fake()->randomElement(EmploymentType::cases()),
            'location' => fake()->randomElement(['Casablanca, Maroc', 'Rabat, Maroc', 'Marrakech, Maroc', 'Remote', 'Paris, France']),
        ];
    }
}
