<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Document> */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(3, true),
            'filename' => fake()->uuid().'.pdf',
            'original_path' => 'pdfs/'.fake()->uuid().'.pdf',
            'metadata' => null,
        ];
    }
}
